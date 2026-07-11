<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class TeacherSubjectAssignment extends Model
{
    protected $fillable = [
        'teacher_id',
        'subject_id',
        'class_id',
        'term_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * Batch equivalent of resolving TimetableEntry::teacher() one row at a
     * time: a single query for a whole term (optionally one class) instead
     * of one query per timetable slot. Keyed by "class_id:subject_id" since
     * that's the pair every caller actually has on hand (a TimetableEntry,
     * or a class+subject being scheduled).
     *
     * @return Collection<string, Teacher>
     */
    public static function activeTeacherMap(int $termId, ?int $classId = null): Collection
    {
        return static::where('term_id', $termId)
            ->where('is_active', true)
            ->when($classId, fn ($query) => $query->where('class_id', $classId))
            ->with('teacher.user')
            ->get()
            ->keyBy(fn (self $assignment) => "{$assignment->class_id}:{$assignment->subject_id}")
            ->map(fn (self $assignment) => $assignment->teacher);
    }
}
