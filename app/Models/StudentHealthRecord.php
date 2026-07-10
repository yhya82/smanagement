<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentHealthRecord extends Model
{
    protected $fillable = [
        'student_id',
        'allergies',
        'conditions',
        'emergency_notes',
        'is_confidential',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_confidential' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(HealthRecordAudit::class, 'health_record_id');
    }
}
