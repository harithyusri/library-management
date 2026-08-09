<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit room bookings');
    }

    public function rules(): array
    {
        return [
            'room_id'             => ['required', 'exists:rooms,id'],
            'booking_date'        => ['required', 'date', 'after_or_equal:today'],
            'start_time'          => ['required', 'date_format:H:i'],
            'end_time'            => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose'             => ['nullable', 'string', 'max:255'],
            'number_of_attendees' => ['nullable', 'integer', 'min:1', 'max:500'],
            'special_requests'    => ['nullable', 'string'],
            'notes'               => ['nullable', 'string'],
            'user_id'             => ['nullable', 'exists:users,id'],
        ];
    }
}
