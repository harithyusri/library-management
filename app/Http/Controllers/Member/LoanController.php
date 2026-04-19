<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoanController extends Controller
{
    /**
     * Display a listing of member's loans.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Loan::with(['bookCopy.book'])
            ->where('user_id', $user->id);

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

        return Inertia::render('members/Loans/Index', [
            'loans' => $loans,
            'filters' => $request->only(['book_search', 'status', 'sort_by', 'sort_order']),
            'statuses' => Loan::getStatuses(),
        ]);
    }
}
