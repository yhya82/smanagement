<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A separate, append-only trail from audit_logs on purpose: audit_logs is
 * shaped around "a specific business record changed" (auditable_type/id),
 * but a failed login or a permission denial usually has no such record -
 * forcing those into the same polymorphic shape would mean a fake/placeholder
 * auditable_id for events that aren't about any model at all.
 */
class SecurityEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'event',
        'email',
        'ip_address',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $event, array $attributes = []): self
    {
        return static::create(array_merge([
            'ip_address' => request()?->ip(),
        ], $attributes, ['event' => $event]));
    }
}
