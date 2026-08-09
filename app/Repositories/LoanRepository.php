<?php

namespace App\Repositories;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LoanRepository
{
    public function paginatedForMember(User $user, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Loan::with(['bookCopy.book'])->where('user_id', $user->id);

        if (! empty($filters['book_search'])) {
            $query->whereHas('bookCopy.book', fn ($q) =>
                $q->where('title', 'like', '%' . $filters['book_search'] . '%')
            );
        }

        $this->applyStatusFilter($query, $filters['status'] ?? null);

        $sortBy    = $filters['sort_by'] ?? 'borrowed_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage)->withQueryString();
    }

    public function paginatedForAdmin(array $filters, ?int $libraryId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Loan::with(['bookCopy.book', 'user']);

        if ($libraryId) {
            $query->withoutGlobalScope(\App\Models\Scopes\LibraryScope::class)
                  ->where('library_id', $libraryId);
        }

        if (! empty($filters['search'])) {
            $query->whereHas('user', fn ($q) =>
                $q->where('name', 'like', '%' . $filters['search'] . '%')
            );
        }

        if (! empty($filters['book_search'])) {
            $query->whereHas('bookCopy.book', fn ($q) =>
                $q->where('title', 'like', '%' . $filters['book_search'] . '%')
            );
        }

        $this->applyStatusFilter($query, $filters['status'] ?? null);

        $sortBy    = $filters['sort_by'] ?? 'borrowed_date';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage)->withQueryString();
    }

    public function finesForMember(User $user): \Illuminate\Support\Collection
    {
        return $user->loans()
            ->with(['bookCopy.book'])
            ->where(fn ($q) => $q->where('fine_amount', '>', 0)->orWhere('fine_paid', true))
            ->orderBy('due_date', 'desc')
            ->get();
    }

    public function finesForAdmin(array $filters, ?int $libraryId = null): \Illuminate\Support\Collection
    {
        $query = Loan::with(['bookCopy.book', 'user'])
            ->whereNotNull('fine_amount')
            ->where('fine_amount', '>', 0);

        if ($libraryId) {
            $query->withoutGlobalScope(\App\Models\Scopes\LibraryScope::class)
                  ->where('library_id', $libraryId);
        }

        if (! empty($filters['search'])) {
            $query->whereHas('user', fn ($q) =>
                $q->where('name', 'like', '%' . $filters['search'] . '%')
            );
        }

        return $query->orderBy('due_date', 'desc')->get();
    }

    private function applyStatusFilter($query, ?string $status): void
    {
        if (! $status || $status === 'all') return;

        match ($status) {
            Loan::STATUS_ACTIVE   => $query->whereNull('returned_date'),
            Loan::STATUS_RETURNED => $query->whereNotNull('returned_date'),
            Loan::STATUS_OVERDUE  => $query->whereNull('returned_date')->where('due_date', '<', now()),
            default               => null,
        };
    }
}
