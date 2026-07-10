<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceEditRequest;
use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttendanceService
{
    /**
     * Marks one or many students at once - the same method covers SRS
     * §16's "individual or bulk" since a single-entry map works fine for
     * the individual case. UNIQUE(student_id, date) catches accidental
     * double-marks at the DB level; this turns that into a per-student
     * result instead of aborting the whole batch.
     *
     * @param  array<int, AttendanceStatus>  $statusesByStudentId
     * @return array{marked: list<int>, already_marked: list<int>}
     */
    public function mark(SchoolClass $class, string $date, array $statusesByStudentId, Teacher $markedBy): array
    {
        if (! $markedBy->hasAccessToClass($class->id)) {
            throw new RuntimeException('Teacher is not assigned to this class.');
        }

        $marked = [];
        $alreadyMarked = [];

        foreach ($statusesByStudentId as $studentId => $status) {
            try {
                AttendanceRecord::create([
                    'student_id' => $studentId,
                    'class_id' => $class->id,
                    'date' => $date,
                    'status' => $status,
                    'marked_by' => $markedBy->id,
                    'marked_at' => now(),
                ]);
                $marked[] = $studentId;
            } catch (QueryException $e) {
                if (! $this->isUniqueViolation($e)) {
                    throw $e;
                }
                $alreadyMarked[] = $studentId;
            }
        }

        return ['marked' => $marked, 'already_marked' => $alreadyMarked];
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return $e->getCode() === '23000';
    }

    /**
     * Only for records past the 7-day direct-edit window (SRS §16) - a
     * record still inside the window should be edited directly instead.
     */
    public function requestEdit(
        AttendanceRecord $record,
        User $requestedBy,
        AttendanceStatus $requestedStatus,
        string $reason
    ): AttendanceEditRequest {
        if ($record->locked_at === null) {
            throw new RuntimeException(
                'This record is still within the 7-day direct-edit window; edit it directly instead of requesting a change.'
            );
        }

        return AttendanceEditRequest::create([
            'attendance_id' => $record->id,
            'requested_by' => $requestedBy->id,
            'reason' => $reason,
            'requested_status' => $requestedStatus,
            'status' => ApprovalStatus::Pending,
        ]);
    }

    public function approveEditRequest(AttendanceEditRequest $request, User $approvedBy): AttendanceRecord
    {
        if ($request->status !== ApprovalStatus::Pending) {
            throw new RuntimeException('This request has already been decided.');
        }

        return DB::transaction(function () use ($request, $approvedBy) {
            $request->update([
                'status' => ApprovalStatus::Approved,
                'approved_by' => $approvedBy->id,
                'approved_at' => now(),
            ]);

            $record = $request->attendanceRecord;
            $record->update(['status' => $request->requested_status]);

            return $record;
        });
    }

    public function rejectEditRequest(AttendanceEditRequest $request, User $rejectedBy): AttendanceEditRequest
    {
        if ($request->status !== ApprovalStatus::Pending) {
            throw new RuntimeException('This request has already been decided.');
        }

        $request->update([
            'status' => ApprovalStatus::Rejected,
            'approved_by' => $rejectedBy->id,
            'approved_at' => now(),
        ]);

        return $request;
    }
}
