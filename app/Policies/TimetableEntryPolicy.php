<?php

namespace App\Policies;

use App\Models\TimetableEntry;
use App\Models\User;

class TimetableEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('timetable.manage') || $user->hasRole('Teacher') || $user->student !== null;
    }

    public function view(User $user, TimetableEntry $entry): bool
    {
        if ($user->student?->current_class_id === $entry->class_id) {
            return true;
        }

        if ($user->hasRole('Teacher')) {
            return $user->teacher?->hasAccessToClass($entry->class_id) ?? false;
        }

        return $user->hasPermission('timetable.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('timetable.manage');
    }

    public function update(User $user, TimetableEntry $entry): bool
    {
        return $user->hasPermission('timetable.manage');
    }

    public function delete(User $user, TimetableEntry $entry): bool
    {
        return $user->hasPermission('timetable.manage');
    }
}
