<?php
namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Loan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    /**
     * Display the member-facing book catalog.
     */
    public function index(Request $request): Response
    {
        $query = Book::with(['genres', 'category', 'publisher'])
            ->withCount(['copies as total_copies'])
            ->withCount(['copies as available_copies' => function ($q) {
                $q->where('status', 'available');
            }]);

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by Genre
        if ($request->filled('genre') && $request->genre !== 'all') {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre);
            });
        }

        // Filter by Category
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        $books = $query->paginate(12)->withQueryString();

        return Inertia::render('members/Catalog/Index', [
            'books'      => $books,
            'filters'    => $request->only(['search', 'genre', 'category']),
            'genres'     => Genre::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'is_member'  => $request->user()?->isMember() ?? false,
        ]);
    }

    /**
     * Display the detailed book view for members.
     */
    public function show(Request $request, Book $book): Response
    {
        $book->load(['genres', 'category', 'publisher', 'copies' => function ($q) {
            $q->with('library')->orderBy('status');
        }]);

        $availableCopiesCount = $book->copies()->where('status', 'available')->count();

        return Inertia::render('members/Catalog/Show', [
            'book'                   => $book,
            'available_copies_count' => $availableCopiesCount,
            'is_member'              => auth()->user()?->isMember() ?? false,
        ]);
    }

    /**
     * Automatically borrow the first available copy of a book.
     */
    public function borrow(Request $request, Book $book)
    {
        $user = $request->user();

        if (!$user->isMember()) {
            return back()->with('error', 'Only members can borrow books.');
        }

        // Find first available copy
        $copy = $book->copies()->where('status', 'available')->first();

        if (!$copy) {
            return back()->with('error', 'No copies are currently available for this book.');
        }

        // Standard 14-day loan
        try {
            DB::transaction(function () use ($user, $copy) {
                Loan::create([
                    'book_copy_id'  => $copy->id,
                    'user_id'       => $user->id,
                    'borrowed_date' => now(),
                    'due_date'      => now()->addDays(14),
                    'status'        => Loan::STATUS_ACTIVE,
                ]);

                $copy->update(['status' => 'borrowed']);
            });

            return redirect()->route('member.catalog.index')
                ->with('success', "You have successfully borrowed \"{$book->title}\". Due date: " . now()->addDays(14)->toFormattedDateString());
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while borrowing the book. Please try again.');
        }
    }
}
