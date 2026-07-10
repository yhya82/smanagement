<?php

namespace App\Policies;

use App\Models\Teacher;
use App\Models\User;

class TeacherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('teachers.manage') || $user->hasPermission('teacher_assignments.manage');
    }

    public function view(User $user, Teacher $teacher): bool
    {
        if ($user->teacher?->id === $teacher->id) {
            return true;
        }

        return $user->hasPermission('teachers.manage') || $user->hasPermission('teacher_assignments.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('teachers.manage');
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $user->hasPermission('teachers.manage');
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $user->hasPermission('teachers.manage');
    }
}
