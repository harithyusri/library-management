<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoanController extends Controller
{
    /**
     * Display a listing of borrowed books.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Loan::query()
            ->with(['bookCopy.book', 'user'])
            ->where('user_id', $user->id);

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search by book title
        if ($request->filled('search')) {
            $query->whereHas('bookCopy.book', function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%');
            });
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'borrowed_date');
        $sortOrder = $request->get('sort_order', 'desc');

        $loans = $query->orderBy($sortBy, $sortOrder)
            ->paginate(10)
            ->appends($request->query());

        return Inertia::render('Loans/Index', [
            'loans' => $loans,
            'filters' => [
                'status' => $request->get('status', 'all'),
                'search' => $request->get('search', ''),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Loans/Create');
    }

    /**
     * Show a single loan/borrowed book.
     */
    public function show(Loan $loan)
    {
        // Authorization check - user can only view their own loans
        if ($loan->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $loan->load(['bookCopy.book', 'user', 'librarian']);

        return Inertia::render('Loans/Show', [
            'loan' => $loan,
        ]);
    }
}
