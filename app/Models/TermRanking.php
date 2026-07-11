<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TermRanking extends Model
{
    protected $fillable = [
        'student_id',
        'class_id',
        'term_id',
        'average',
        'position',
        'is_tied',
        'computed_at',
        'remark',
    ];

    protected function casts(): array
    {
        return [
            'average' => 'decimal:2',
            'is_tied' => 'boolean',
            'computed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
