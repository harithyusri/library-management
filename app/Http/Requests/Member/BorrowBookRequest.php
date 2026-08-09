<?php

namespace App\Http\Requests\Member;

use App\Models\Library;
use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class BorrowBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user->isMember()) {
            return false;
        }

        $book      = $this->route('book');
        $libraryId = $this->input('library_id');
        $query     = $book?->copies()->where('status', 'available');
        if ($libraryId) {
            $query->where('library_id', $libraryId);
        }
        $copy    = $query->first();
        $library = $copy?->library;
        $limit   = $library ? $library->getBorrowLimit() : Library::DEFAULT_BORROW_LIMIT;

        $active = $user->loans()
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->count();

        return $active < $limit;
    }

    public function rules(): array
    {
        return [
            'library_id' => ['nullable', 'integer', 'exists:libraries,id'],
        ];
    }

    public function failedAuthorization()
    {
        $user      = $this->user();
        $book      = $this->route('book');
        $libraryId = $this->input('library_id');
        $query     = $book?->copies()->where('status', 'available');
        if ($libraryId) {
            $query->where('library_id', $libraryId);
        }
        $copy    = $query->first();
        $library = $copy?->library;
        $limit   = $library ? $library->getBorrowLimit() : Library::DEFAULT_BORROW_LIMIT;

        return back()->with('error', "You have reached the borrow limit of {$limit} books. Please return a book before borrowing another.");
    }
}
