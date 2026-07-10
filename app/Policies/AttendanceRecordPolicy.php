<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * No permission grants Registrar attendance visibility in the SRS (§7
     * only lists it among what Registrar cannot *edit*) - so view is
     * deliberately limited to the scoped teacher, the student themself, and
     * Administrator (Gate::before bypass), not opened up to Registrar too.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Teacher') || $user->student !== null;
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        if ($user->student?->id === $record->student_id) {
            return true;
        }

        if ($user->hasRole('Teacher')) {
            return $user->teacher?->hasAccessToClass($record->class_id) ?? false;
        }

        return false;
    }

    public function create(User $user, int $classId): bool
    {
        return $user->hasPermission('attendance.mark') && ($user->teacher?->hasAccessToClass($classId) ?? false);
    }

    /**
     * Direct edits are only allowed within the 7-day window (SRS §16);
     * once attendance_records.locked_at is set, this returns false and the
     * teacher must go through AttendanceEditRequest instead.
     */
    public function update(User $user, AttendanceRecord $record): bool
    {
        if ($record->locked_at !== null) {
            return false;
        }

        return $user->hasPermission('attendance.edit')
            && ($user->teacher?->hasAccessToClass($record->class_id) ?? false);
    }

    public function delete(User $user, AttendanceRecord $record): bool
    {
        return false;
    }
}
