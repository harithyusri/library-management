<?php

namespace App\Policies;

use App\Models\MaintenanceReport;
use App\Models\User;

class MaintenanceReportPolicy
{
    public function view(User $user, MaintenanceReport $report): bool
    {
        return $user->isStaff() || $report->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isMember() || $user->isStaff();
    }

    public function update(User $user): bool
    {
        return $user->isStaff();
    }
}
