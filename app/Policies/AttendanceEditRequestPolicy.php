<?php

namespace App\Policies;

use App\Models\AttendanceEditRequest;
use App\Models\User;

class AttendanceEditRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.edit') || $user->hasPermission('attendance.edit.approve');
    }

    public function view(User $user, AttendanceEditRequest $request): bool
    {
        if ($request->requested_by === $user->id) {
            return true;
        }

        return $user->hasPermission('attendance.edit.approve');
    }

    public function create(User $user, int $classId): bool
    {
        return $user->hasPermission('attendance.edit') && ($user->teacher?->hasAccessToClass($classId) ?? false);
    }

    public function approve(User $user, AttendanceEditRequest $request): bool
    {
        return $user->hasPermission('attendance.edit.approve');
    }
}
