<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableEntry extends Model
{
    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    protected $fillable = [
        'class_id',
        'term_id',
        'period_id',
        'day_of_week',
        'subject_id',
    ];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Deliberately not a stored column (see the migration's own comment) -
     * looked up fresh each time so the timetable can never show a stale
     * teacher after a class's subject assignment changes.
     */
    public function teacher(): ?Teacher
    {
        return TeacherSubjectAssignment::where([
            'class_id' => $this->class_id,
            'subject_id' => $this->subject_id,
            'term_id' => $this->term_id,
            'is_active' => true,
        ])->first()?->teacher;
    }
}
