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
}
