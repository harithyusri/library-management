<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;

class PayFineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $loan = $this->route('loan');
        return $loan->user_id === $this->user()->id && ! $loan->fine_paid;
    }

    public function rules(): array
    {
        $loan = $this->route('loan');
        $remaining = $loan->fine_amount - ($loan->fine_paid_amount ?? 0);

        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:' . $remaining],
        ];
    }
}
