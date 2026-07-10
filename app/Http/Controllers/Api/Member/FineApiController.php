<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Models\FinePayment;
use App\Models\Loan;
use App\Support\ApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class FineApiController extends Controller
{
    public function index(Request $request): JsonResponse
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
            ->map(fn ($loan) => ApiFormatter::fine($loan));

        return response()->json([
            'fines' => $fines,
            'total_unpaid' => $user->getTotalUnpaidFines(),
        ]);
    }

    public function show(Request $request, Loan $loan): JsonResponse
    {
        if ($loan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $loan->load(['bookCopy.book', 'bookCopy.book.publisher', 'payments']);

        return response()->json([
            'fine' => ApiFormatter::fine($loan, detailed: true),
        ]);
    }

    public function pay(Request $request, Loan $loan): JsonResponse
    {
        if ($loan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $remaining = $loan->fine_amount - ($loan->fine_paid_amount ?? 0);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:'.$remaining],
            'success_url' => ['nullable', 'url'],
            'cancel_url' => ['nullable', 'url'],
        ]);

        $amountToPay = $validated['amount'];

        Stripe::setApiKey(config('services.stripe.secret'));

        $successUrl = $validated['success_url'] ?? config('app.url').'/member/fines/'.$loan->id.'/success?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = $validated['cancel_url'] ?? config('app.url').'/member/fines/'.$loan->id;

        $session = Session::create([
            'payment_method_types' => ['card', 'fpx'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'myr',
                    'product_data' => [
                        'name' => 'Fine Payment: '.$loan->bookCopy->book->title,
                        'description' => 'Partial payment for loan Ref #LOAN-'.$loan->id,
                    ],
                    'unit_amount' => (int) round($amountToPay * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        FinePayment::create([
            'loan_id' => $loan->id,
            'user_id' => $request->user()->id,
            'amount' => $amountToPay,
            'stripe_session_id' => $session->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'checkout_url' => $session->url,
            'session_id' => $session->id,
        ]);
    }
}
