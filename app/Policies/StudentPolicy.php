<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('students.view');
    }

    /**
     * SRS §13: teachers only see students in assigned classes. SRS §20:
     * students only see their own records. Registrar/anyone else holding
     * students.view sees all (Administrator bypasses via Gate::before).
     */
    public function view(User $user, Student $student): bool
    {
        if ($user->student?->id === $student->id) {
            return true;
        }

        if (! $user->hasPermission('students.view')) {
            return false;
        }

        if ($user->hasRole('Teacher')) {
            return $user->teacher?->hasAccessToStudent($student) ?? false;
        }

        return true;
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasPermission('students.manage');
    }

    public function delete(User $user, Student $student): bool
    {
        return false;
    }
}
