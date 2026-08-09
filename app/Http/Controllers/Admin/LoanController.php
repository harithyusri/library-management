<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReturnLoanRequest;
use App\Http\Requests\Admin\StoreLoanRequest;
use App\Models\BookCopy;
use App\Models\Library;
use App\Models\Loan;
use App\Models\User;
use App\Repositories\LoanRepository;
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
        $user          = $request->user();
        $isSuperAdmin  = $user->hasRole('Super Admin');
        $libraries     = [];
        $selectedLibraryId = null;

        if ($isSuperAdmin) {
            $libraries         = Library::where('is_active', true)->orderBy('name')->get(['id', 'name']);
            $selectedLibraryId = $request->integer('library_id') ?: null;
        }

        $loans = $this->loans->paginatedForAdmin(
            $request->only(['search', 'book_search', 'status', 'sort_by', 'sort_order']),
            $isSuperAdmin ? $selectedLibraryId : null
        );

        return Inertia::render('admins/Loans/Index', [
            'loans'               => $loans,
            'filters'             => $request->only(['search', 'book_search', 'status', 'sort_by', 'sort_order']),
            'statuses'            => Loan::getStatuses(),
            'libraries'           => $libraries,
            'selected_library_id' => $selectedLibraryId,
            'is_super_admin'      => $isSuperAdmin,
        ]);
    }

    /**
     * Show the form for creating a new loan.
     */
    public function create()
    {
        // Get users for the borrower dropdown
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        // You might also need BookCopies that are 'available'
        $availableCopies = BookCopy::with('book')
            ->where('status', 'available')
            ->get();

        return Inertia::render('admins/Loans/Create', [
            'users' => $users,
            'availableCopies' => $availableCopies
        ]);
    }

    public function store(StoreLoanRequest $request)
    {
        try {
            $loan = $this->loanService->issueLoan($request->validated(), $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['book_copy_id' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.loans.show', $loan->id)
            ->with('success', 'Loan created successfully!');
    }

    /**
     * Display the specified loan.
     */
    public function show(Loan $loan)
    {
        $loan->load(['bookCopy.book', 'user', 'librarian']);

        // Calculate potential fine if overdue and not yet returned
        if (!$loan->returned_date && $loan->due_date < now()) {
            $daysOverdue = now()->diffInDays($loan->due_date);
            $loan->fine_amount = $daysOverdue * 1.00; // RM1 per day
        }

        return Inertia::render('admins/Loans/Show', [
            'loan' => $loan,
            'audits' => $loan->audits()->with('user:id,name')->latest()->get(),
        ]);
    }

    /**
     * Search for available book copies (AJAX).
     */
    public function searchBookCopies(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $copies = BookCopy::with('book:id,title,author_name,isbn')
            ->where('status', 'available')
            ->where(function ($q) use ($query) {
                $q->where('barcode', 'like', "%{$query}%")
                  ->orWhere('call_number', 'like', "%{$query}%")
                  ->orWhereHas('book', function ($bookQuery) use ($query) {
                      $bookQuery->where('title', 'like', "%{$query}%")
                                ->orWhere('author_name', 'like', "%{$query}%")
                                ->orWhere('isbn', 'like', "%{$query}%");
                  });
            })
            ->limit(20)
            ->get();

        return response()->json(['data' => $copies]);
    }

    public function return(ReturnLoanRequest $request, Loan $loan)
    {
        try {
            $this->loanService->returnBook($loan, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Book returned successfully!');
    }
}
