<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Loan;
use App\Support\ApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Book::with(['genres', 'category', 'publisher'])
            ->withCount(['copies as total_copies'])
            ->withCount(['copies as available_copies' => function ($q) {
                $q->where('status', 'available');
            }]);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('genre') && $request->genre !== 'all') {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre);
            });
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        $books = $query->paginate($request->integer('per_page', 12));

        return response()->json([
            'data' => collect($books->items())->map(fn ($b) => ApiFormatter::book($b))->values(),
            'meta' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
            'filters' => [
                'genres' => Genre::orderBy('name')->get(['id', 'name']),
                'categories' => Category::orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function show(Book $book): JsonResponse
    {
        return response()->json([
            'book' => ApiFormatter::book($book, detailed: true),
        ]);
    }

    public function borrow(Request $request, Book $book): JsonResponse
    {
        $user = $request->user();

        $copy = $book->copies()->where('status', 'available')->first();

        if (! $copy) {
            return response()->json(['message' => 'No copies are currently available for this book.'], 422);
        }

        try {
            DB::transaction(function () use ($user, $copy) {
                Loan::create([
                    'book_copy_id' => $copy->id,
                    'user_id' => $user->id,
                    'borrowed_date' => now(),
                    'due_date' => now()->addDays(14),
                    'status' => Loan::STATUS_ACTIVE,
                    'library_id' => $copy->library_id,
                ]);

                $copy->update(['status' => 'borrowed']);
            });

            return response()->json([
                'message' => "You have successfully borrowed \"{$book->title}\".",
                'due_date' => now()->addDays(14)->toDateString(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while borrowing the book.'], 500);
        }
    }
}
