<?php

namespace App\Jobs;

use App\Models\SchoolClass;
use App\Models\User;
use App\Notifications\StudentImportCompleted;
use App\Notifications\StudentImportFailed;
use App\Services\StudentImportService;
use App\Support\SafeNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * A large CSV import running synchronously inside the request that
 * uploaded it risks a request timeout well before it finishes - the file
 * is persisted to a private disk by the Livewire component before this is
 * dispatched (its own temporary upload wouldn't survive past that request),
 * and this job deletes it once done regardless of outcome.
 */
class ImportStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Deliberately no automatic retry: each CSV row commits its own
     * student/guardian/enrollment independently, so a retry after a
     * partial failure would re-process rows that already succeeded and
     * create duplicate students - and the uploaded file is already
     * deleted by the first attempt's handle(), so a retry would just fail
     * again anyway with a misleading "file not found" error that masks
     * what actually went wrong. One attempt, a clean failed() log and
     * notification, and the admin re-uploads if needed.
     */
    public int $tries = 1;

    /**
     * Only IDs are kept as properties, never the SchoolClass/User instances
     * themselves - see ComputeRankingsJob's identical properties for why:
     * storing an Eloquent model as a job property makes SerializesModels
     * re-fetch it (and throw ModelNotFoundException if it's gone) on every
     * deserialization, including the one Laravel triggers internally while
     * reporting a failure - which happens before this job's own failed()
     * is ever reached. Plain IDs avoid that path entirely.
     */
    private readonly int $classId;

    private readonly int $importedById;

    public function __construct(
        SchoolClass $class,
        private readonly string $storedFilePath,
        User $importedBy,
    ) {
        $this->classId = $class->id;
        $this->importedById = $importedBy->id;

        $this->onQueue('bulk');
    }

    public function handle(StudentImportService $importService): void
    {
        $class = SchoolClass::find($this->classId);

        if (! $class) {
            Log::warning('ImportStudentsJob: target class no longer exists, discarding upload.', ['class_id' => $this->classId]);
            Storage::disk('local')->delete($this->storedFilePath);

            return;
        }

        try {
            $result = $importService->import(Storage::disk('local')->path($this->storedFilePath), $class);

            Log::info('Student import complete', [
                'class_id' => $class->id,
                'created' => $result['created'],
                'failed' => count($result['errors']),
            ]);

            if ($importedBy = User::find($this->importedById)) {
                SafeNotifier::send($importedBy, new StudentImportCompleted($class, $result['created'], count($result['errors'])));
            } else {
                Log::warning('ImportStudentsJob: importing user no longer exists, completion notification skipped.', ['user_id' => $this->importedById]);
            }
        } finally {
            Storage::disk('local')->delete($this->storedFilePath);
        }
    }

    public function failed(Throwable $exception): void
    {
        Storage::disk('local')->delete($this->storedFilePath);

        Log::error('ImportStudentsJob failed', ['class_id' => $this->classId, 'exception' => $exception->getMessage()]);

        if ($importedBy = User::find($this->importedById)) {
            SafeNotifier::send($importedBy, new StudentImportFailed($this->classId));
        }
    }
}
