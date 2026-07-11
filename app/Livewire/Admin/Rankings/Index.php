<?php

namespace App\Livewire\Admin\Rankings;

use App\Enums\ResultStatus;
use App\Jobs\ComputeRankingsJob;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\TermRanking;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Wires RankingService/ComputeRankings up to an admin action - previously
 * reachable only via `php artisan rankings:compute`, run by hand, with
 * nowhere to view the results afterward either.
 */
#[Layout('components.app-layout')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $termId = '';

    #[Url]
    public string $classId = '';

    public ?string $computeResult = null;

    public function mount(): void
    {
        $this->authorize('viewAny', TermRanking::class);

        $this->termId = (string) (Term::active()?->id ?? '');
    }

    public function updatingClassId(): void
    {
        $this->resetPage();
    }

    public function updatingTermId(): void
    {
        $this->resetPage();
    }

    /**
     * The actual per-class computation is dispatched to a queued job -
     * doing it here, synchronously, risked a request timeout at a school
     * with enough classes and result history. The empty-results check
     * stays here since it's a cheap, fast rejection worth giving
     * immediately rather than after a round trip through the queue.
     */
    public function compute(): void
    {
        $this->authorize('viewAny', TermRanking::class);

        $this->computeResult = null;

        $this->validate(['termId' => ['required', 'exists:terms,id']], [], ['termId' => 'term']);

        $term = Term::findOrFail($this->termId);

        $hasApprovedResults = ResultEntry::where('term_id', $term->id)
            ->where('status', ResultStatus::Approved)
            ->exists();

        if (! $hasApprovedResults) {
            $this->computeResult = "No approved results found for '{$term->name}'.";

            return;
        }

        ComputeRankingsJob::dispatch($term, Auth::user());

        $this->computeResult = "Ranking computation for '{$term->name}' queued - you'll get a notification when it's done.";
    }

    public function render()
    {
        $rankings = TermRanking::with(['student', 'schoolClass'])
            ->when($this->termId, fn ($query) => $query->where('term_id', $this->termId))
            ->when($this->classId, fn ($query) => $query->where('class_id', $this->classId))
            ->orderBy('class_id')
            ->orderBy('position')
            ->paginate(20);

        return view('livewire.admin.rankings.index', [
            'rankings' => $rankings,
            'terms' => Term::orderBy('name')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
        ]);
    }
}
