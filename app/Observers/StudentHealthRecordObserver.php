<?php

namespace App\Observers;

use App\Models\HealthRecordAudit;
use App\Models\StudentHealthRecord;
use RuntimeException;

/**
 * SRS §11: "health changes are audited" - the one audit trail the SRS names
 * explicitly, into its own dedicated table (not the generic audit_logs).
 */
class StudentHealthRecordObserver
{
    private const TRACKED_FIELDS = ['allergies', 'conditions', 'emergency_notes', 'is_confidential'];

    /**
     * Guards *before* the write happens (Eloquent's `updated` event fires
     * after the row is already committed, which is too late to prevent an
     * un-audited change - a first version of this observer threw from
     * `updated()` and still left the change persisted with no audit row).
     */
    public function saving(StudentHealthRecord $record): void
    {
        // Only guards updates to an existing record - creation isn't
        // audited (there's no prior state to log a change against), and
        // every attribute looks "dirty" on a brand new record regardless.
        if (! $record->exists || ! $record->isDirty(self::TRACKED_FIELDS)) {
            return;
        }

        if (! $record->updated_by && ! auth()->id()) {
            throw new RuntimeException(
                'Cannot save a health record change without a changed_by user - set updated_by on the record or save it within an authenticated context.'
            );
        }
    }

    public function updated(StudentHealthRecord $record): void
    {
        if (! $record->wasChanged(self::TRACKED_FIELDS)) {
            return;
        }

        $original = [];
        $current = [];
        foreach ($record->getChanges() as $key => $value) {
            if (in_array($key, self::TRACKED_FIELDS, true)) {
                $original[$key] = $record->getOriginal($key);
                $current[$key] = $value;
            }
        }

        HealthRecordAudit::create([
            'health_record_id' => $record->id,
            'changed_by' => $record->updated_by ?? auth()->id(),
            'old_values' => $original,
            'new_values' => $current,
        ]);
    }
}
