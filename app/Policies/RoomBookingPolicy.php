<?php

namespace App\Policies;

use App\Models\RoomBooking;
use App\Models\User;

class RoomBookingPolicy
{
    public function view(User $user, RoomBooking $booking): bool
    {
        return $user->isStaff() || $booking->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff() || $user->isMember();
    }

    public function edit(User $user, RoomBooking $booking): bool
    {
        return $user->isStaff() && $booking->status === 'pending';
    }

    public function updateStatus(User $user): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, RoomBooking $booking): bool
    {
        return $user->isStaff();
    }
}
