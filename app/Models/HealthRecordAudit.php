<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRecordAudit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'health_record_id',
        'changed_by',
        'old_values',
        'new_values',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function healthRecord(): BelongsTo
    {
        return $this->belongsTo(StudentHealthRecord::class, 'health_record_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
