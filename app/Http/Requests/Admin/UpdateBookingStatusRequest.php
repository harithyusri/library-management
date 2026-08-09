<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit room bookings');
    }

    public function rules(): array
    {
        return [
            'status'              => ['required', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'cancellation_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:500'],
        ];
    }
}
