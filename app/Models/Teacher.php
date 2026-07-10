<?php

namespace App\Models;

use App\Enums\TeacherStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'employee_no',
        'status',
        'primary_class_id',
        'hire_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => TeacherStatus::class,
            'hire_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function primaryClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'primary_class_id');
    }

    public function homeroomClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'homeroom_teacher_id');
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class);
    }

    public function attendanceRecordsMarked(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'marked_by');
    }

    public function resultEntriesEntered(): HasMany
    {
        return $this->hasMany(ResultEntry::class, 'entered_by');
    }

    /**
     * SRS §13: teachers can only view/act on students in assigned
     * classes/subjects. teacher_subject_assignments is the single source of
     * truth for that scope - every policy that needs to check "can this
     * teacher touch this class" goes through here rather than re-querying it.
     */
    public function hasAccessToClass(int $classId): bool
    {
        return $this->subjectAssignments()
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->exists();
    }

    public function hasAccessToStudent(Student $student): bool
    {
        if ($student->current_class_id === null) {
            return false;
        }

        return $this->hasAccessToClass($student->current_class_id);
    }

    /**
     * Grade entry is scoped to the specific subject a teacher is assigned
     * to teach in a class, not just the class as a whole (SRS §13: "assigned
     * classes/grades/subjects") - a teacher assigned only Math in Class A
     * has no business entering English grades for that same class.
     */
    public function hasAccessToClassSubject(int $classId, int $subjectId): bool
    {
        return $this->subjectAssignments()
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->exists();
    }
}
