<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\Gender;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentApplication extends Model
{
    use Auditable;

    protected $fillable = [
        'first_name',
        'last_name',
        'dob',
        'gender',
        'submitted_by',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'student_id',
        'documents_purged_at',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'gender' => Gender::class,
            'status' => ApprovalStatus::class,
            'reviewed_at' => 'datetime',
            'documents_purged_at' => 'datetime',
        ];
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }
}
