<?php

namespace App\Policies;

use App\Models\Period;
use App\Models\User;

class PeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Period $period): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('timetable.manage');
    }

    public function update(User $user, Period $period): bool
    {
        return $user->hasPermission('timetable.manage');
    }

    public function delete(User $user, Period $period): bool
    {
        return $user->hasPermission('timetable.manage');
    }
}
