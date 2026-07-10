<?php

namespace App\Policies;

use App\Models\TeacherSubjectAssignment;
use App\Models\User;

class TeacherSubjectAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('teacher_assignments.manage') || $user->hasRole('Teacher');
    }

    public function view(User $user, TeacherSubjectAssignment $assignment): bool
    {
        if ($user->teacher?->id === $assignment->teacher_id) {
            return true;
        }

        return $user->hasPermission('teacher_assignments.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('teacher_assignments.manage');
    }

    public function update(User $user, TeacherSubjectAssignment $assignment): bool
    {
        return $user->hasPermission('teacher_assignments.manage');
    }

    public function delete(User $user, TeacherSubjectAssignment $assignment): bool
    {
        return $user->hasPermission('teacher_assignments.manage');
    }
}
