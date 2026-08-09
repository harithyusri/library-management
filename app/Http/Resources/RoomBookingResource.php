<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'room'                 => $this->whenLoaded('room', fn () => array_merge(
                $this->room->only(['id', 'name', 'room_number', 'hourly_rate', 'library_id']),
                [
                    'image_url' => $this->room->image ? asset('storage/' . $this->room->image) : null,
                    'library'   => $this->room->relationLoaded('library') ? $this->room->library?->only(['id', 'name']) : null,
                ]
            )),
            'user'                 => $this->whenLoaded('user'),
            'booking_date'         => $this->booking_date->toDateString(),
            'start_time'           => substr($this->start_time, 0, 5),
            'end_time'             => substr($this->end_time, 0, 5),
            'status'               => $this->status,
            'purpose'              => $this->purpose,
            'number_of_attendees'  => $this->number_of_attendees,
            'special_requests'     => $this->special_requests,
            'notes'                => $this->notes,
            'cancellation_reason'  => $this->cancellation_reason,
            'approved_by'          => $this->approved_by,
            'approved_at'          => $this->approved_at,
            'cancelled_at'         => $this->cancelled_at,
            'duration_hours'       => $this->duration_in_hours,
            'total_cost'           => $this->total_cost ?? 0,
            'created_at'           => $this->created_at,
        ];
    }
}
