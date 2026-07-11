<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Term extends Model
{
    protected $fillable = [
        'academic_year_id',
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function teacherSubjectAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class);
    }

    public function resultEntries(): HasMany
    {
        return $this->hasMany(ResultEntry::class);
    }

    public function termRankings(): HasMany
    {
        return $this->hasMany(TermRanking::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * The active term is read by well over a dozen call sites, most of
     * them on every single request - cached with a broad, safe
     * invalidation (any Term write at all busts it) rather than trying to
     * track exactly which write flips is_active.
     */
    public static function active(): ?self
    {
        return Cache::remember('active_term', now()->addHour(), fn () => static::where('is_active', true)->first());
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('active_term'));
        static::deleted(fn () => Cache::forget('active_term'));
    }
}
