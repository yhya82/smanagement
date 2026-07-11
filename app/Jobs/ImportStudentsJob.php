<?php

namespace App\Jobs;

use App\Models\SchoolClass;
use App\Models\User;
use App\Notifications\StudentImportCompleted;
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
     * what actually went wrong. One attempt, a clean failed() log, and the
     * admin re-uploads if needed.
     */
    public int $tries = 1;

    /**
     * See ComputeRankingsJob's identical property for why this matters:
     * without it, a deleted Class/User between dispatch and execution
     * makes this job vanish with no log at all, and the stored upload
     * leaks on disk forever.
     */
    public bool $deleteWhenMissingModels = false;

    public function __construct(
        private readonly SchoolClass $class,
        private readonly string $storedFilePath,
        private readonly User $importedBy,
    ) {}

    public function handle(StudentImportService $importService): void
    {
        try {
            $result = $importService->import(Storage::disk('local')->path($this->storedFilePath), $this->class);

            Log::info('Student import complete', [
                'class_id' => $this->class->id,
                'created' => $result['created'],
                'failed' => count($result['errors']),
            ]);

            SafeNotifier::send($this->importedBy, new StudentImportCompleted($this->class, $result['created'], count($result['errors'])));
        } finally {
            Storage::disk('local')->delete($this->storedFilePath);
        }
    }

    public function failed(Throwable $exception): void
    {
        Storage::disk('local')->delete($this->storedFilePath);

        Log::error('ImportStudentsJob failed', ['class_id' => $this->class->id, 'exception' => $exception->getMessage()]);
    }
}
