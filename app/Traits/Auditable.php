<?php

namespace App\Traits;

use App\Models\AuditLog;

/**
 * Generic "record changed" logging into audit_logs for models whose
 * lifecycle is basically approvals (SRS §21: "log approvals, ...,
 * promotions, ..." with user, timestamp, and affected record) - so each one
 * doesn't need its own bespoke observer just to write the same shape of row.
 *
 * Not used for student_health_records: those changes go to the dedicated
 * health_record_audits table instead (SRS §11), handled by
 * StudentHealthRecordObserver.
 */
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if (empty($changes)) {
                return;
            }

            $original = [];
            foreach (array_keys($changes) as $key) {
                $original[$key] = $model->getOriginal($key);
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => class_basename($model).'.updated',
                'auditable_type' => static::class,
                'auditable_id' => $model->getKey(),
                'old_values' => $original,
                'new_values' => $changes,
            ]);
        });
    }
}
