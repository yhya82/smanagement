<?php

namespace App\Livewire\Admin;

use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Services\ResultService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class GradeReviewIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $classId = '';

    #[Url]
    public string $subjectId = '';

    public function updatingClassId(): void
    {
        $this->resetPage();
    }

    public function updatingSubjectId(): void
    {
        $this->resetPage();
    }

    public function approve(ResultEntry $result, ResultService $resultService): void
    {
        $this->authorize('approve', $result);

        $resultService->approve($result, Auth::user());
    }

    public function reject(ResultEntry $result, ResultService $resultService): void
    {
        $this->authorize('approve', $result);

        $resultService->reject($result, Auth::user());
    }

    public function render()
    {
        // Every subject's every class lands in one flat queue by default -
        // with enough classes and subjects in play, an admin working
        // through it needs to narrow to one class/subject at a time rather
        // than hunting through a mixed list.
        $results = ResultEntry::where('status', ResultStatus::Submitted)
            ->when($this->classId, fn ($query) => $query->where('class_id', $this->classId))
            ->when($this->subjectId, fn ($query) => $query->where('subject_id', $this->subjectId))
            ->with(['student', 'subject', 'schoolClass', 'term'])
            ->oldest()
            ->paginate(15);

        return view('livewire.admin.grade-review-index', [
            'results' => $results,
            'classes' => SchoolClass::orderBy('name')->get(),
            'subjects' => Subject::orderBy('name')->get(),
        ]);
    }
}
