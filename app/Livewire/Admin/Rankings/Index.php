<?php

namespace App\Livewire\Admin\Rankings;

use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\TermRanking;
use App\Services\RankingService;
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

        $this->termId = (string) (Term::where('is_active', true)->first()?->id ?? '');
    }

    public function updatingClassId(): void
    {
        $this->resetPage();
    }

    public function updatingTermId(): void
    {
        $this->resetPage();
    }

    public function compute(RankingService $rankingService): void
    {
        $this->authorize('viewAny', TermRanking::class);

        $this->computeResult = null;

        $this->validate(['termId' => ['required', 'exists:terms,id']], [], ['termId' => 'term']);

        $term = Term::findOrFail($this->termId);

        $classIds = ResultEntry::where('term_id', $term->id)
            ->where('status', ResultStatus::Approved)
            ->distinct()
            ->pluck('class_id');

        if ($classIds->isEmpty()) {
            $this->computeResult = "No approved results found for '{$term->name}'.";

            return;
        }

        $classCount = 0;
        $rankingCount = 0;

        foreach (SchoolClass::whereIn('id', $classIds)->get() as $class) {
            $rankings = $rankingService->computeForClassTerm($class, $term);
            $classCount++;
            $rankingCount += $rankings->count();
        }

        $this->computeResult = "Computed {$rankingCount} ranking(s) across {$classCount} class(es) for '{$term->name}'.";
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
