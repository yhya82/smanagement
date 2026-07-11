<?php

namespace App\Jobs;

use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\User;
use App\Notifications\RankingComputeCompleted;
use App\Notifications\RankingComputeFailed;
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
     * Each class's ranking is computed via updateOrCreate (idempotent), so
     * a retry can't duplicate data the way a re-run of the import job
     * could - but a retry still just re-does work the per-class isolation
     * below already handled gracefully, so there's no benefit to one
     * either. One attempt, clean failed() log + notification.
     */
    public int $tries = 1;

    /**
     * Only the IDs are kept as properties, never the Term/User instances
     * themselves. SerializesModels re-fetches any Eloquent-model *property*
     * from the database on every deserialization - including the one
     * Laravel's own CallQueuedHandler::failed() triggers internally when
     * reporting a failure - and throws ModelNotFoundException if the row
     * is gone. Traced through the framework: that second, internal
     * deserialization attempt is never caught, so if this job's Term/User
     * property is what's missing, this job's own failed() method is never
     * reached at all - the exact scenario this job needs to handle
     * gracefully. Storing plain IDs sidesteps the framework's model-rehydration
     * machinery entirely: a missing row just becomes a null from find(),
     * handled explicitly below instead of thrown.
     */
    private readonly int $termId;

    private readonly int $requestedById;

    public function __construct(Term $term, User $requestedBy)
    {
        $this->termId = $term->id;
        $this->requestedById = $requestedBy->id;

        $this->onQueue('bulk');
    }

    public function handle(RankingService $rankingService): void
    {
        $term = Term::find($this->termId);

        if (! $term) {
            Log::warning('ComputeRankingsJob: term no longer exists, nothing to compute.', ['term_id' => $this->termId]);

            return;
        }

        $classIds = ResultEntry::where('term_id', $term->id)
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
                $rankings = $rankingService->computeForClassTerm($class, $term);
                $classCount++;
                $rankingCount += $rankings->count();
            } catch (Throwable $e) {
                $failedCount++;
                Log::error('Failed to compute rankings for one class', [
                    'term_id' => $term->id,
                    'class_id' => $class->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Ranking compute run complete', [
            'term_id' => $term->id,
            'class_count' => $classCount,
            'ranking_count' => $rankingCount,
            'failed_count' => $failedCount,
        ]);

        if ($requestedBy = User::find($this->requestedById)) {
            SafeNotifier::send($requestedBy, new RankingComputeCompleted($term, $classCount, $rankingCount, $failedCount));
        } else {
            Log::warning('ComputeRankingsJob: requester no longer exists, completion notification skipped.', ['user_id' => $this->requestedById]);
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ComputeRankingsJob failed', ['term_id' => $this->termId, 'exception' => $exception->getMessage()]);

        if ($requestedBy = User::find($this->requestedById)) {
            SafeNotifier::send($requestedBy, new RankingComputeFailed($this->termId));
        }
    }
}
