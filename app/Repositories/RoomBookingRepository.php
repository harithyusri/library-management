<?php

namespace App\Repositories;

use App\Models\RoomBooking;
use App\Models\User;
use Illuminate\Support\Collection;

class RoomBookingRepository
{
    public function forMember(User $user): Collection
    {
        return RoomBooking::with(['room:id,name,room_number,hourly_rate,library_id', 'room.library'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function forAdmin(?int $libraryId = null): Collection
    {
        $query = RoomBooking::with(['room:id,name,room_number,hourly_rate', 'user:id,name']);

        if ($libraryId) {
            $query->withoutGlobalScope(\App\Models\Scopes\LibraryScope::class)
                  ->where('library_id', $libraryId);
        }

        return $query->orderBy('booking_date')->orderBy('start_time')->get();
    }

    public function activeConflicts(int $roomId, string $date, string $start, string $end, ?int $excludeId = null): bool
    {
        return RoomBooking::where('room_id', $roomId)
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(fn ($q) => $q->where('start_time', '<', $end)->where('end_time', '>', $start))
            ->exists();
    }

    public function pendingBookingsForCalendar(): Collection
    {
        return RoomBooking::whereIn('status', ['pending', 'confirmed'])
            ->select('room_id', 'booking_date', 'start_time', 'end_time')
            ->get();
    }
}
