<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\BorrowBookRequest;
use App\Models\Book;
use App\Models\Category;
use App\Models\Genre;
use App\Repositories\BookRepository;
use App\Services\LoanService;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function __construct(
        private BookRepository $books,
        private LoanService $loanService,
        private RecommendationService $recommendations,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('members/Catalog/Index', [
            'books'      => $this->books->paginatedCatalog($request->only(['search', 'genre', 'category'])),
            'filters'    => $request->only(['search', 'genre', 'category']),
            'genres'     => $this->books->allGenres(),
            'categories' => $this->books->allCategories(),
            'is_member'  => $request->user()?->isMember() ?? false,
        ]);
    }

    public function show(Request $request, Book $book): Response
    {
        $book->load(['genres', 'category', 'publisher', 'copies' => fn ($q) =>
            $q->with('library')->orderBy('status')
        , 'reviews' => fn ($q) => $q->with('user:id,name')->latest()]);

        $user = $request->user();

        $hasActiveReservation = $user?->isMember()
            ? \App\Models\Reservation::where('user_id', $user->id)
                ->where('book_id', $book->id)
                ->whereIn('status', ['pending', 'ready'])
                ->exists()
            : false;

        $hasBorrowed = $user?->isMember()
            ? $user->loans()
                ->whereHas('bookCopy', fn ($q) => $q->where('book_id', $book->id))
                ->whereNotNull('returned_date')
                ->exists()
            : false;

        $userReview = $user?->isMember()
            ? $book->reviews->firstWhere('user_id', $user->id)
            : null;

        $recommendedBooks = ($user?->isMember())
            ? $this->recommendations->forUser($user, $book)
            : collect();

        return Inertia::render('members/Catalog/Show', [
            'book'                   => $book,
            'available_copies_count' => $book->copies()->where('status', 'available')->count(),
            'is_member'              => $user?->isMember() ?? false,
            'has_active_reservation' => $hasActiveReservation,
            'has_borrowed'           => $hasBorrowed,
            'user_review'            => $userReview,
            'recommended_books'      => $recommendedBooks->map(fn ($b) => [
                'id'               => $b->id,
                'title'            => $b->title,
                'author_name'      => $b->author_name,
                'cover_image'      => $b->cover_image_url,
                'category'         => $b->category?->only('id', 'name'),
                'genres'           => $b->genres->map->only('id', 'name')->values(),
                'available_copies' => $b->copies->where('status', 'available')->count(),
            ]),
        ]);
    }

    public function borrow(BorrowBookRequest $request, Book $book)
    {
        $loan = $this->loanService->borrowBook($request->user(), $book, $request->input('library_id'));

        return redirect()->route('member.catalog.index')
            ->with('success', "You have successfully borrowed \"{$book->title}\". Due date: {$loan->due_date->toFormattedDateString()}");
    }
}
