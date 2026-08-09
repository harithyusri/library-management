<?php

namespace App\Repositories;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BookRepository
{
    public function paginatedCatalog(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        $query = Book::with(['genres', 'category', 'publisher'])
            ->withCount(['copies as total_copies'])
            ->withCount(['copies as available_copies' => fn ($q) => $q->where('status', 'available')]);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['genre']) && $filters['genre'] !== 'all') {
            $query->whereHas('genres', fn ($q) => $q->where('genres.id', $filters['genre']));
        }

        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category_id', $filters['category']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginatedAdmin(array $filters, ?int $libraryId = null, int $perPage = 12): LengthAwarePaginator
    {
        $query = Book::with(['genres', 'category', 'publisher', 'library']);

        if ($libraryId) {
            $query->withoutGlobalScope(\App\Models\Scopes\LibraryScope::class)
                  ->where('library_id', $libraryId);
        }

        if (! empty($filters['search'])) {
            $query->where(fn ($q) => $q
                ->where('title', 'like', '%' . $filters['search'] . '%')
                ->orWhere('author_name', 'like', '%' . $filters['search'] . '%')
                ->orWhere('isbn', 'like', '%' . $filters['search'] . '%')
            );
        }

        if (! empty($filters['genre']) && $filters['genre'] !== 'all') {
            $query->whereHas('genres', fn ($q) => $q->where('genres.id', $filters['genre']));
        }

        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category_id', $filters['category']);
        }

        if (! empty($filters['format']) && $filters['format'] !== 'all') {
            $query->where('format', $filters['format']);
        }

        if (! empty($filters['language']) && $filters['language'] !== 'all') {
            $query->where('language', $filters['language']);
        }

        $allowedSorts = ['created_at', 'title', 'published_year'];
        $sortBy    = in_array($filters['sort_by'] ?? null, $allowedSorts) ? $filters['sort_by'] : 'created_at';
        $sortOrder = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage)->withQueryString();
    }

    public function allGenres(): Collection
    {
        return Genre::orderBy('name')->get();
    }

    public function allCategories(): Collection
    {
        return Category::orderBy('name')->get();
    }
}
