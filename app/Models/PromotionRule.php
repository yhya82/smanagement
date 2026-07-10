<?php

namespace App\Models;

use App\Enums\PromotionCriteriaType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionRule extends Model
{
    protected $fillable = [
        'grade_level_id',
        'criteria_type',
        'threshold_value',
        'target_class_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'criteria_type' => PromotionCriteriaType::class,
            'threshold_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function targetClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'target_class_id');
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }
}
