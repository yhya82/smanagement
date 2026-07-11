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
