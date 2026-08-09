<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReturnLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    public function rules(): array
    {
        return [
            'returned_date'   => ['required', 'date'],
            'condition_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
