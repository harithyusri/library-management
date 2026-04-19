<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\FinePayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class FineController extends Controller
{
    /**
     * Display a listing of the member's fines.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        $fines = $user->loans()
            ->with(['bookCopy.book'])
            ->where(function ($query) {
                $query->where('fine_amount', '>', 0)
                      ->orWhere('fine_paid', true);
            })
            ->orderBy('due_date', 'desc')
            ->get()
            ->map(function ($loan) {
                return [
                    'id' => $loan->id,
                    'book_title' => $loan->bookCopy->book->title,
                    'due_date' => $loan->due_date->format('d M Y'),
                    'fine_amount' => (float) $loan->fine_amount,
                    'fine_paid' => (bool) $loan->fine_paid,
                    'fine_paid_amount' => (float) ($loan->fine_paid_amount ?? 0),
                    'remaining_amount' => (float) ($loan->fine_amount - ($loan->fine_paid_amount ?? 0)),
                    'status' => $this->getFineStatus($loan),
                ];
            });

        return Inertia::render('members/Fines/Index', [
            'fines' => $fines,
            'total_unpaid' => $user->getTotalUnpaidFines(),
        ]);
    }

    /**
     * Display the specified fine details.
     */
    public function show(Loan $loan): Response
    {
        // Ensure user owns the loan
        if ($loan->user_id !== auth()->id()) {
            abort(403);
        }

        $loan->load(['bookCopy.book', 'bookCopy.book.publisher', 'payments' => function($query) {
            $query->where('status', 'paid')->orderBy('paid_at', 'desc');
        }]);

        return Inertia::render('members/Fines/Show', [
            'fine' => [
                'id' => $loan->id,
                'book' => [
                    'title' => $loan->bookCopy->book->title,
                    'cover' => $loan->bookCopy->book->cover_image_path,
                    'publisher' => $loan->bookCopy->book->publisher->name,
                ],
                'borrowed_date' => $loan->borrowed_date->format('d M Y'),
                'due_date' => $loan->due_date->format('d M Y'),
                'returned_date' => $loan->returned_date?->format('d M Y'),
                'fine_amount' => (float) $loan->fine_amount,
                'fine_paid' => (bool) $loan->fine_paid,
                'fine_paid_amount' => (float) ($loan->fine_paid_amount ?? 0),
                'remaining_amount' => (float) ($loan->fine_amount - ($loan->fine_paid_amount ?? 0)),
                'status' => $this->getFineStatus($loan),
                'payments' => $loan->payments->map(fn($p) => [
                    'id' => $p->id,
                    'amount' => (float) $p->amount,
                    'method' => $p->payment_method,
                    'date' => $p->paid_at->format('d M Y, h:i A'),
                    'stripe_id' => $p->stripe_payment_intent_id ?? '',
                ]),
            ]
        ]);
    }

    /**
     * Handle payment initiation with Stripe.
     */
    public function pay(Request $request, Loan $loan)
    {
        $remaining = $loan->fine_amount - ($loan->fine_paid_amount ?? 0);
        
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:' . $remaining],
        ]);

        $amountToPay = $request->amount;

        Stripe::setApiKey(config('services.stripe.secret'));

        $session = Session::create([
            'payment_method_types' => ['card', 'fpx'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => "Fine Payment: " . $loan->bookCopy->book->title,
                        'description' => "Partial payment for loan Ref #LOAN-" . $loan->id,
                    ],
                    'unit_amount' => $amountToPay * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('member.fines.success', $loan->id) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('member.fines.show', $loan->id),
        ]);

        // Create a pending payment record
        FinePayment::create([
            'loan_id' => $loan->id,
            'user_id' => auth()->id(),
            'amount' => $amountToPay,
            'stripe_session_id' => $session->id,
            'status' => 'pending',
        ]);

        return Inertia::location($session->url);
    }

    /**
     * Handle success callback from Stripe.
     */
    public function success(Request $request, Loan $loan)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $sessionId = $request->get('session_id');
        
        $session = Session::retrieve([
            'id' => $sessionId,
            'expand' => ['payment_intent.payment_method']
        ]);
        
        $payment = FinePayment::where('stripe_session_id', $sessionId)->first();

        if (!$payment) {
            return redirect()->route('member.fines.show', $loan->id)->with('error', 'Payment record not found.');
        }

        if ($session->payment_status === 'paid' && $payment->status !== 'paid') {
            // Get the actual payment method used (card, fpx, etc.)
            $paymentMethodType = $session->payment_intent->payment_method->type ?? 'card';
            
            // Update the specific payment record
            $payment->update([
                'status' => 'paid',
                'stripe_payment_intent_id' => $session->payment_intent->id,
                'payment_method' => $paymentMethodType,
                'paid_at' => now(),
            ]);

            // Update the Loan overall status
            $totalPaid = $loan->payments()->where('status', 'paid')->sum('amount');
            $loan->update([
                'fine_paid_amount' => $totalPaid,
                'fine_paid' => $totalPaid >= $loan->fine_amount,
            ]);
            
            return redirect()->route('member.fines.show', $loan->id)->with('success', 'Payment of RM ' . number_format($payment->amount, 2) . ' confirmed!');
        }

        return redirect()->route('member.fines.show', $loan->id)->with('error', 'Payment verification failed.');
    } 

    /**
     * Download receipt for a settled fine.
     */
    public function downloadReceipt(Loan $loan)
    {
        if ($loan->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$loan->fine_paid) {
            return back()->with('error', 'Receipt only available for settled fines.');
        }

        $loan->load(['user', 'bookCopy.book', 'payments' => function($query) {
            $query->where('status', 'paid')->orderBy('paid_at', 'asc');
        }]);

        $pdf = Pdf::loadView('fines.receipt', compact('loan'));
        
        return $pdf->download('receipt-' . $loan->id . '.pdf');
    }

    /**
     * Helper to get fine status string.
     */
    private function getFineStatus(Loan $loan): string
    {
        if ($loan->fine_paid) return 'settled';
        if ($loan->fine_paid_amount > 0) return 'partial';
        return 'unpaid';
    }
}
