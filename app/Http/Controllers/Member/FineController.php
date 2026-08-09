<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\PayFineRequest;
use App\Http\Resources\FineDetailResource;
use App\Http\Resources\FineResource;
use App\Models\Loan;
use App\Repositories\LoanRepository;
use App\Services\FineService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FineController extends Controller
{
    public function __construct(
        private LoanRepository $loans,
        private FineService $fineService,
    ) {}

    public function index(Request $request): Response
    {
        $user  = $request->user();
        $fines = $this->loans->finesForMember($user);

        return Inertia::render('members/Fines/Index', [
            'fines'        => FineResource::collection($fines)->resolve(),
            'total_unpaid' => $user->getTotalUnpaidFines(),
        ]);
    }

    public function show(Loan $loan): Response
    {
        $this->authorize('view', $loan);

        $loan->load(['bookCopy.book.publisher', 'payments' => fn ($q) =>
            $q->where('status', 'paid')->orderBy('paid_at', 'desc')
        ]);

        return Inertia::render('members/Fines/Show', [
            'fine' => new FineDetailResource($loan),
        ]);
    }

    public function pay(PayFineRequest $request, Loan $loan)
    {
        $loan->loadMissing('bookCopy.book');
        $session = $this->fineService->createStripeSession($loan, $request->validated('amount'));

        return Inertia::location($session->url);
    }

    public function success(Request $request, Loan $loan)
    {
        $payment = $this->fineService->handleSuccess($loan, $request->get('session_id'));

        if (! $payment) {
            return redirect()->route('member.fines.show', $loan->id)
                ->with('error', 'Payment verification failed.');
        }

        return redirect()->route('member.fines.show', $loan->id)
            ->with('success', 'Payment of RM ' . number_format($payment->amount, 2) . ' confirmed!');
    }

    public function downloadReceipt(Loan $loan)
    {
        $this->authorize('downloadReceipt', $loan);

        $loan->load(['user', 'bookCopy.book', 'payments' => fn ($q) =>
            $q->where('status', 'paid')->orderBy('paid_at', 'asc')
        ]);

        return Pdf::loadView('fines.receipt', compact('loan'))
            ->download('receipt-' . $loan->id . '.pdf');
    }
}
