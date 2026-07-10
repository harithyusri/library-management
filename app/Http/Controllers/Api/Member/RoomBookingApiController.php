<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Models\Library;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Support\ApiFormatter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomBookingApiController extends Controller
{
    private function formatTime(string $time): string
    {
        return substr($time, 0, 5);
    }

    private function calcDuration(string $start, string $end): float
    {
        $s = Carbon::createFromFormat('H:i', $start);
        $e = Carbon::createFromFormat('H:i', $end);

        return $e->diffInMinutes($s) / 60;
    }

    /**
     * @return array<string, list<string>>
     */
    private function bookingRules(): array
    {
        return [
            'room_id' => ['required', 'exists:rooms,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'number_of_attendees' => ['nullable', 'integer', 'min:1', 'max:500'],
            'special_requests' => ['nullable', 'string'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function hasConflict(array $validated): bool
    {
        return RoomBooking::where('room_id', $validated['room_id'])
            ->whereDate('booking_date', $validated['booking_date'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($validated) {
                $q->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })
            ->exists();
    }

    public function index(Request $request): JsonResponse
    {
        $bookings = RoomBooking::with(['room:id,name,room_number,hourly_rate,library_id', 'room.library'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn ($b) => ApiFormatter::roomBooking($b));

        return response()->json(['bookings' => $bookings]);
    }

    public function createData(Request $request): JsonResponse
    {
        $rooms = Room::where('status', 'available')
            ->select('id', 'name', 'room_number', 'type', 'capacity', 'floor', 'hourly_rate', 'status', 'description', 'image', 'library_id')
            ->with('library')
            ->get()
            ->map(fn ($r) => ApiFormatter::room($r));

        $existingBookings = RoomBooking::whereIn('status', ['pending', 'confirmed'])
            ->select('room_id', 'booking_date', 'start_time', 'end_time')
            ->get()
            ->map(fn ($b) => [
                'room_id' => $b->room_id,
                'booking_date' => $b->booking_date->toDateString(),
                'start_time' => $this->formatTime($b->start_time),
                'end_time' => $this->formatTime($b->end_time),
            ]);

        return response()->json([
            'rooms' => $rooms,
            'libraries' => Library::orderBy('name')->get(['id', 'name']),
            'existing_bookings' => $existingBookings,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->bookingRules());

        $room = Room::findOrFail($validated['room_id']);

        if ($room->status !== 'available') {
            return response()->json(['message' => 'This room is not available for booking.'], 422);
        }

        if ($this->hasConflict($validated)) {
            return response()->json(['message' => 'This time slot is already booked. Please choose a different time.'], 422);
        }

        $duration = $this->calcDuration($validated['start_time'], $validated['end_time']);
        $cost = round($room->hourly_rate * $duration, 2);

        $booking = RoomBooking::create([
            'room_id' => $validated['room_id'],
            'user_id' => $request->user()->id,
            'booking_date' => $validated['booking_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'pending',
            'purpose' => $validated['purpose'] ?? null,
            'number_of_attendees' => $validated['number_of_attendees'] ?? null,
            'special_requests' => $validated['special_requests'] ?? null,
            'total_cost' => $cost,
            'library_id' => $room->library_id,
        ]);

        $booking->load(['room.library']);

        return response()->json([
            'message' => 'Booking requested successfully! It will be reviewed by our staff.',
            'booking' => ApiFormatter::roomBooking($booking),
        ], 201);
    }
}
