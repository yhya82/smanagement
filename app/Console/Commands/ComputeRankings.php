<?php

namespace App\Console\Commands;

use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Services\RankingService;
use Illuminate\Console\Command;

/**
 * Deliberately not scheduled (unlike attendance:lock / applications:purge-
 * documents): ranking runs when a term actually closes, an explicit admin
 * action per SRS §14 ("only one term may be active"), not on a fixed
 * calendar cadence. Invoked on demand from the CLI or an admin action once
 * Phase 11's controllers exist.
 */
class ComputeRankings extends Command
{
    protected $signature = 'rankings:compute {term : The ID of the term to compute rankings for}';

    protected $description = 'Compute term rankings for every class with approved results in the given term';

    public function handle(RankingService $rankingService): int
    {
        $term = Term::find($this->argument('term'));

        if (! $term) {
            $this->error("Term #{$this->argument('term')} not found.");

            return self::FAILURE;
        }

        $classIds = ResultEntry::where('term_id', $term->id)
            ->where('status', ResultStatus::Approved)
            ->distinct()
            ->pluck('class_id');

        if ($classIds->isEmpty()) {
            $this->warn("No approved results found for term '{$term->name}'.");

            return self::SUCCESS;
        }

        foreach (SchoolClass::whereIn('id', $classIds)->get() as $class) {
            $rankings = $rankingService->computeForClassTerm($class, $term);
            $this->info("Computed {$rankings->count()} ranking(s) for class '{$class->name}'.");
        }

        return self::SUCCESS;
    }
}
