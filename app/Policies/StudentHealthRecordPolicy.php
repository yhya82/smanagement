<?php

namespace App\Policies;

use App\Models\StudentHealthRecord;
use App\Models\User;

class StudentHealthRecordPolicy
{
    /**
     * SRS §11: emergency info (allergies, asthma, etc.) is visible to any
     * teacher responsible for the student - same class-assignment scope as
     * everything else a teacher can see, not a separate permission.
     */
    public function viewEmergencyInfo(User $user, StudentHealthRecord $record): bool
    {
        if ($user->student?->id === $record->student_id) {
            return true;
        }

        if ($user->hasRole('Teacher')) {
            return $user->teacher?->hasAccessToStudent($record->student) ?? false;
        }

        return $user->hasPermission('health_records.view.full') || $user->hasPermission('health_records.manage');
    }

    /**
     * Full record (detailed medical history) is permission-controlled
     * (SRS §11), not just class-scoped like the emergency summary.
     */
    public function view(User $user, StudentHealthRecord $record): bool
    {
        if ($user->student?->id === $record->student_id) {
            return true;
        }

        return $user->hasPermission('health_records.view.full') || $user->hasPermission('health_records.manage');
    }

    public function update(User $user, StudentHealthRecord $record): bool
    {
        return $user->hasPermission('health_records.manage');
    }

    public function delete(User $user, StudentHealthRecord $record): bool
    {
        return false;
    }
}
