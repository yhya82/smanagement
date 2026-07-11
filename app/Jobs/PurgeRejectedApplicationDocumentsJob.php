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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

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
        $purged = 0;
        $failed = 0;

        StudentApplication::query()
            ->where('status', ApprovalStatus::Rejected)
            ->where('reviewed_at', '<=', now()->subDays(90))
            ->whereNull('documents_purged_at')
            ->each(function (StudentApplication $application) use (&$purged, &$failed) {
                try {
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

                    $purged++;
                } catch (Throwable $e) {
                    // One bad record (e.g. a document already missing from
                    // disk) must not block every other application's purge -
                    // this compliance-relevant job needs to keep going and
                    // surface exactly which one failed, not die silently on
                    // the first problem it hits.
                    $failed++;
                    Log::error('Failed to purge a rejected application\'s documents', [
                        'application_id' => $application->id,
                        'exception' => $e->getMessage(),
                    ]);
                }
            });

        Log::info('Rejected application document purge run complete', ['purged' => $purged, 'failed' => $failed]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('PurgeRejectedApplicationDocumentsJob failed', ['exception' => $exception->getMessage()]);
    }
}
