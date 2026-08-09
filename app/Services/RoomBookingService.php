<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\User;
use App\Repositories\RoomBookingRepository;
use Carbon\Carbon;

class RoomBookingService
{
    public function __construct(private RoomBookingRepository $repo) {}

    /**
     * Calculate duration in hours between two HH:MM strings.
     */
    public function calcDuration(string $start, string $end): float
    {
        return Carbon::createFromFormat('H:i', $end)
            ->diffInMinutes(Carbon::createFromFormat('H:i', $start)) / 60;
    }

    /**
     * Calculate total cost for a booking.
     */
    public function calcCost(Room $room, float $durationHours): float
    {
        return round($room->hourly_rate * $durationHours, 2);
    }

    /**
     * Check whether a time slot conflicts with existing bookings.
     */
    public function hasConflict(int $roomId, string $date, string $start, string $end, ?int $excludeId = null): bool
    {
        return $this->repo->activeConflicts($roomId, $date, $start, $end, $excludeId);
    }

    /**
     * Create a new room booking.
     */
    public function createBooking(array $validated, User $user): RoomBooking
    {
        $room = Room::findOrFail($validated['room_id']);

        if ($room->status !== 'available') {
            throw new \RuntimeException('This room is not available for booking.');
        }

        if ($this->hasConflict($validated['room_id'], $validated['booking_date'], $validated['start_time'], $validated['end_time'])) {
            throw new \RuntimeException('This time slot is already booked. Please choose a different time.');
        }

        $duration = $this->calcDuration($validated['start_time'], $validated['end_time']);
        $cost     = $this->calcCost($room, $duration);

        return RoomBooking::create([
            'room_id'             => $validated['room_id'],
            'user_id'             => $validated['user_id'] ?? $user->id,
            'booking_date'        => $validated['booking_date'],
            'start_time'          => $validated['start_time'],
            'end_time'            => $validated['end_time'],
            'status'              => 'pending',
            'purpose'             => $validated['purpose'] ?? null,
            'number_of_attendees' => $validated['number_of_attendees'] ?? null,
            'special_requests'    => $validated['special_requests'] ?? null,
            'notes'               => $validated['notes'] ?? null,
            'total_cost'          => $cost,
            'library_id'          => $room->library_id,
        ]);
    }

    /**
     * Update an existing pending booking.
     */
    public function updateBooking(RoomBooking $booking, array $validated): RoomBooking
    {
        if ($booking->status !== 'pending') {
            throw new \RuntimeException('Only pending bookings can be edited.');
        }

        if ($this->hasConflict($validated['room_id'], $validated['booking_date'], $validated['start_time'], $validated['end_time'], $booking->id)) {
            throw new \RuntimeException('This time slot is already booked. Please choose a different time.');
        }

        $room     = Room::findOrFail($validated['room_id']);
        $duration = $this->calcDuration($validated['start_time'], $validated['end_time']);
        $cost     = $this->calcCost($room, $duration);

        $booking->update([
            'room_id'             => $validated['room_id'],
            'user_id'             => $validated['user_id'] ?? $booking->user_id,
            'booking_date'        => $validated['booking_date'],
            'start_time'          => $validated['start_time'],
            'end_time'            => $validated['end_time'],
            'purpose'             => $validated['purpose'] ?? null,
            'number_of_attendees' => $validated['number_of_attendees'] ?? null,
            'special_requests'    => $validated['special_requests'] ?? null,
            'notes'               => $validated['notes'] ?? null,
            'total_cost'          => $cost,
            'library_id'          => $room->library_id,
        ]);

        return $booking->fresh();
    }

    /**
     * Transition a booking to a new status with validation.
     */
    public function updateStatus(RoomBooking $booking, string $newStatus, User $approver, ?string $cancellationReason = null): RoomBooking
    {
        $allowed = match ($booking->status) {
            'pending'   => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled'],
            'cancelled' => ['pending'],
            default     => [],
        };

        if (! in_array($newStatus, $allowed)) {
            throw new \RuntimeException("Cannot change status from '{$booking->status}' to '{$newStatus}'.");
        }

        $updates = ['status' => $newStatus];

        if ($newStatus === 'confirmed') {
            $updates['approved_by'] = $approver->id;
            $updates['approved_at'] = now();
        }

        if ($newStatus === 'cancelled') {
            $updates['cancellation_reason'] = $cancellationReason;
            $updates['cancelled_at']        = now();
        }

        if ($newStatus === 'pending') {
            $updates['cancellation_reason'] = null;
            $updates['cancelled_at']        = null;
        }

        $booking->update($updates);

        return $booking->fresh();
    }

    /**
     * Format a time string to HH:MM.
     */
    public function formatTime(string $time): string
    {
        return substr($time, 0, 5);
    }
}
