<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Named SchoolClass, not Class, since `class` is a reserved word in PHP.
 * Backs the `classes` table.
 */
class SchoolClass extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'grade_level_id',
        'academic_year_id',
        'name',
        'homeroom_teacher_id',
        'capacity',
    ];

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    public function teacherSubjectAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class, 'class_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'current_class_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'class_id');
    }

    public function resultEntries(): HasMany
    {
        return $this->hasMany(ResultEntry::class, 'class_id');
    }

    public function termRankings(): HasMany
    {
        return $this->hasMany(TermRanking::class, 'class_id');
    }
}
