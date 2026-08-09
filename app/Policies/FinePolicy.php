<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class FinePolicy
{
    public function view(User $user, Loan $loan): bool
    {
        return $user->isStaff() || $loan->user_id === $user->id;
    }

    public function pay(User $user, Loan $loan): bool
    {
        return $loan->user_id === $user->id && ! $loan->fine_paid;
    }

    public function downloadReceipt(User $user, Loan $loan): bool
    {
        return ($user->isStaff() || $loan->user_id === $user->id) && $loan->fine_paid;
    }
}
