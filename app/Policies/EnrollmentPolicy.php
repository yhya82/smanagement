<?php

namespace App\Policies;

use App\Enums\EnrollmentSource;
use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('enrollment.manage') || $user->hasRole('Teacher');
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->student?->id === $enrollment->student_id) {
            return true;
        }

        if ($user->hasRole('Teacher')) {
            return $user->teacher?->hasAccessToClass($enrollment->class_id) ?? false;
        }

        return $user->hasPermission('enrollment.manage');
    }

    public function create(User $user, ?EnrollmentSource $source = null): bool
    {
        return $source === EnrollmentSource::Import
            ? $user->hasPermission('enrollment.import')
            : $user->hasPermission('enrollment.manage');
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->hasPermission('enrollment.manage');
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return false;
    }
}
