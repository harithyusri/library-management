<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Repositories\LoanRepository;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function __construct(
        private LoanRepository $loans,
        private LoanService $loanService,
    ) {}

    public function index(Request $request)
    {
        $loans = $this->loans->paginatedForMember(
            $request->user(),
            $request->only(['book_search', 'status', 'sort_by', 'sort_order'])
        );

        return Inertia::render('members/Loans/Index', [
            'loans'       => $loans,
            'filters'     => $request->only(['book_search', 'status', 'sort_by', 'sort_order']),
            'statuses'    => Loan::getStatuses(),
            'max_renewals' => LoanService::MAX_RENEWALS,
        ]);
    }

    public function renew(Request $request, Loan $loan)
    {
        abort_if($loan->user_id !== $request->user()->id, 403);

        try {
            $this->loanService->renewLoan($loan);
            return back()->with('success', 'Loan renewed successfully. New due date: ' . $loan->fresh()->due_date->toFormattedDateString());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
