<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function view(User $user, Loan $loan): bool
    {
        return $user->isStaff() || $loan->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function return(User $user, Loan $loan): bool
    {
        return $user->isStaff() && is_null($loan->returned_date);
    }

    public function borrow(User $user): bool
    {
        return $user->isMember();
    }
}
