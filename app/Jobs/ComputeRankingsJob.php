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

        foreach (SchoolClass::whereIn('id', $classIds)->get() as $class) {
            $rankings = $rankingService->computeForClassTerm($class, $this->term);
            $classCount++;
            $rankingCount += $rankings->count();
        }

        Log::info('Ranking compute complete', ['term_id' => $this->term->id, 'class_count' => $classCount, 'ranking_count' => $rankingCount]);

        SafeNotifier::send($this->requestedBy, new RankingComputeCompleted($this->term, $classCount, $rankingCount));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ComputeRankingsJob failed', ['term_id' => $this->term->id, 'exception' => $exception->getMessage()]);
    }
}
