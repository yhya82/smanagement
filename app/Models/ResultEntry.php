<?php

namespace App\Models;

use App\Enums\ExamType;
use App\Enums\ResultStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultEntry extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'class_id',
        'term_id',
        'exam_type',
        'entered_by',
        'score',
        'max_score',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'exam_type' => ExamType::class,
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'status' => ResultStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function percentage(): float
    {
        return round(((float) $this->score / (float) $this->max_score) * 100, 2);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
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

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'entered_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
