<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\User;
use App\Models\BookCopy;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    /**
     * Display a listing of loans.
     */
    public function index(Request $request)
    {
        $query = Loan::with(['bookCopy.book', 'user']);

        // Search by borrower name
        if ($request->has('search') && $request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            });
        }

        // Search by book title
        if ($request->has('book_search') && $request->book_search) {
            $query->whereHas('bookCopy.book', function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->book_search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === Loan::STATUS_ACTIVE) {
                $query->whereNull('returned_date');
            } elseif ($request->status === Loan::STATUS_RETURNED) {
                $query->whereNotNull('returned_date');
            } elseif ($request->status === Loan::STATUS_OVERDUE) {
                $query->whereNull('returned_date')
                    ->where('due_date', '<', now());
            }
        }

        // Sort
        $sortBy = $request->get('sort_by', 'borrowed_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $loans = $query->paginate(15)->withQueryString();

        return Inertia::render('admins/Loans/Index', [
            'loans' => $loans,
            'filters' => $request->only(['search', 'book_search', 'status', 'sort_by', 'sort_order']),
            'statuses' => Loan::getStatuses(),
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

    /**
     * Store a newly created loan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'book_copy_id' => 'required|exists:book_copies,id',
            'borrowed_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:borrowed_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Check if book copy is available
        $bookCopy = BookCopy::findOrFail($validated['book_copy_id']);

        if ($bookCopy->status !== 'available') {
            return redirect()->back()
                ->withErrors(['book_copy_id' => 'This book copy is not available for borrowing.'])
                ->withInput();
        }

        // Create the loan
        $loan = Loan::create([
            'user_id' => $validated['user_id'],
            'book_copy_id' => $validated['book_copy_id'],
            'librarian_id' => Auth::id(), // Current logged-in librarian
            'borrowed_date' => $validated['borrowed_date'],
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'active',
            'library_id' => $bookCopy->library_id,
        ]);

        // Update book copy status
        $bookCopy->update(['status' => 'borrowed']);

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

    /**
     * Return a borrowed book.
     */
    public function return(Request $request, Loan $loan)
    {
        if ($loan->returned_date) {
            return redirect()->back()->with('error', 'This book has already been returned.');
        }

        $validated = $request->validate([
            'returned_date' => 'required|date',
            'condition_notes' => 'nullable|string|max:1000',
        ]);

        // Update loan
        $loan->update([
            'returned_date' => $validated['returned_date'],
            'status' => 'returned',
            'notes' => $loan->notes . "\n\nReturn notes: " . ($validated['condition_notes'] ?? ''),
        ]);

        // Update book copy status back to available
        $loan->bookCopy->update(['status' => 'available']);

        // Calculate fine if overdue
        if ($loan->due_date < $validated['returned_date']) {
            $daysOverdue = (strtotime($validated['returned_date']) - strtotime($loan->due_date)) / 86400;
            $fineAmount = $daysOverdue * 1.00; // $1 per day

            $loan->update([
                'fine_amount' => $fineAmount,
                'fine_paid' => false,
            ]);
        }

        return redirect()->back()->with('success', 'Book returned successfully!');
    }
}
