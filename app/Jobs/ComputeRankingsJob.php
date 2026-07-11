<?php

namespace App\Jobs;

use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\User;
use App\Notifications\RankingComputeCompleted;
use App\Services\RankingService;
use App\Support\SafeNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Computing every class's rankings for a term synchronously, in the same
 * request that triggered it, risks a timeout at a school with enough
 * classes and result history - moved off the request thread for the same
 * reason as ImportStudentsJob.
 */
class ComputeRankingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * If the Term or User this job was dispatched for is deleted before a
     * worker picks it up, Laravel's default behavior is to discard the job
     * entirely - not even failed() runs. Explicitly false so a missing
     * model surfaces as a normal, logged job failure instead of the job
     * silently vanishing with no trace.
     */
    public bool $deleteWhenMissingModels = false;

    /**
     * Each class's ranking is computed via updateOrCreate (idempotent), so
     * a retry can't duplicate data the way a re-run of the import job
     * could - but a retry still just re-does work the per-class isolation
     * below already handled gracefully, so there's no benefit to one
     * either. One attempt, clean failed() log.
     */
    public int $tries = 1;

    public function __construct(
        private readonly Term $term,
        private readonly User $requestedBy,
    ) {}

    public function handle(RankingService $rankingService): void
    {
        $classIds = ResultEntry::where('term_id', $this->term->id)
            ->where('status', ResultStatus::Approved)
            ->distinct()
            ->pluck('class_id');

        $classCount = 0;
        $rankingCount = 0;
        $failedCount = 0;

        // Each class is isolated: a lock timeout from a concurrent compute,
        // or any other single-class failure, must not abort every other
        // class still left in the loop, and must not cost the admin their
        // completion notification - see PurgeRejectedApplicationDocumentsJob
        // for the same reasoning applied to per-application isolation.
        foreach (SchoolClass::whereIn('id', $classIds)->get() as $class) {
            try {
                $rankings = $rankingService->computeForClassTerm($class, $this->term);
                $classCount++;
                $rankingCount += $rankings->count();
            } catch (Throwable $e) {
                $failedCount++;
                Log::error('Failed to compute rankings for one class', [
                    'term_id' => $this->term->id,
                    'class_id' => $class->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Ranking compute run complete', [
            'term_id' => $this->term->id,
            'class_count' => $classCount,
            'ranking_count' => $rankingCount,
            'failed_count' => $failedCount,
        ]);

        SafeNotifier::send($this->requestedBy, new RankingComputeCompleted($this->term, $classCount, $rankingCount, $failedCount));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ComputeRankingsJob failed', ['term_id' => $this->term->id, 'exception' => $exception->getMessage()]);
    }
}
