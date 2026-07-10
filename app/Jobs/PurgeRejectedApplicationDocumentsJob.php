<?php

namespace App\Jobs;

use App\Enums\ApprovalStatus;
use App\Models\AuditLog;
use App\Models\StudentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Retention policy from the schema review §2.4: birth certificates, photos,
 * and guardian contact details are sensitive PII that shouldn't be kept
 * forever once an application is rejected. Runs 90 days after the decision,
 * once, tracked via documents_purged_at so it's never re-processed.
 */
class PurgeRejectedApplicationDocumentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        StudentApplication::query()
            ->where('status', ApprovalStatus::Rejected)
            ->where('reviewed_at', '<=', now()->subDays(90))
            ->whereNull('documents_purged_at')
            ->each(function (StudentApplication $application) {
                DB::transaction(function () use ($application) {
                    foreach ($application->documents as $document) {
                        Storage::disk('documents')->delete($document->file_path);
                        $document->delete();
                    }

                    $application->guardians()->delete();

                    $application->update(['documents_purged_at' => now()]);

                    AuditLog::create([
                        'user_id' => null,
                        'action' => 'documents_purged',
                        'auditable_type' => StudentApplication::class,
                        'auditable_id' => $application->id,
                    ]);
                });
            });
    }
}
