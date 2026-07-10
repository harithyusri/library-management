<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Support\ApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Loan::with(['bookCopy.book'])
            ->where('user_id', $user->id);

        if ($request->filled('book_search')) {
            $query->whereHas('bookCopy.book', function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->book_search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === Loan::STATUS_ACTIVE) {
                $query->whereNull('returned_date');
            } elseif ($request->status === Loan::STATUS_RETURNED) {
                $query->whereNotNull('returned_date');
            } elseif ($request->status === Loan::STATUS_OVERDUE) {
                $query->whereNull('returned_date')
                    ->where('due_date', '<', now());
            }
        }

        $sortBy = $request->get('sort_by', 'borrowed_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $loans = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => collect($loans->items())->map(fn ($l) => ApiFormatter::loan($l))->values(),
            'meta' => [
                'current_page' => $loans->currentPage(),
                'last_page' => $loans->lastPage(),
                'per_page' => $loans->perPage(),
                'total' => $loans->total(),
            ],
            'statuses' => Loan::getStatuses(),
        ]);
    }
}
