<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    public function rules(): array
    {
        return [
            'user_id'      => ['required', 'exists:users,id'],
            'book_copy_id' => ['required', 'exists:book_copies,id'],
            'borrowed_date'=> ['required', 'date'],
            'due_date'     => ['required', 'date', 'after_or_equal:borrowed_date'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}
