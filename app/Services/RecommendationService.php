<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Collection;

class RecommendationService
{
    /**
     * Returns up to $limit books recommended for the user based on their borrow history.
     * Scoring: +3 per matching genre, +2 for same author, +1 for same category.
     * Excludes the current book and books the user has already borrowed.
     */
    public function forUser(User $user, Book $currentBook, int $limit = 6): Collection
    {
        // Collect all books the user has ever borrowed
        $borrowedBookIds = $user->loans()
            ->with('bookCopy:id,book_id')
            ->get()
            ->pluck('bookCopy.book_id')
            ->filter()
            ->unique();

        if ($borrowedBookIds->isEmpty()) {
            return collect();
        }

        // Build preference profile from borrowed books
        $borrowedBooks = Book::with('genres')
            ->whereIn('id', $borrowedBookIds)
            ->get();

        $preferredGenreIds = $borrowedBooks->flatMap(fn ($b) => $b->genres->pluck('id'))->countBy()->sortDesc();
        $preferredAuthors  = $borrowedBooks->pluck('author_name')->countBy()->sortDesc();
        $preferredCategoryIds = $borrowedBooks->pluck('category_id')->filter()->countBy()->sortDesc();

        // Candidate pool: exclude current book and already-borrowed books
        $candidates = Book::with(['genres', 'category', 'copies'])
            ->where('id', '!=', $currentBook->id)
            ->whereNotIn('id', $borrowedBookIds)
            ->get();

        return $candidates
            ->map(function (Book $book) use ($preferredGenreIds, $preferredAuthors, $preferredCategoryIds) {
                $score = 0;

                foreach ($book->genres as $genre) {
                    $score += ($preferredGenreIds->get($genre->id) ?? 0) * 3;
                }

                $score += ($preferredAuthors->get($book->author_name) ?? 0) * 2;
                $score += ($preferredCategoryIds->get($book->category_id) ?? 0) * 1;

                return ['book' => $book, 'score' => $score];
            })
            ->filter(fn ($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('book')
            ->values();
    }
}
