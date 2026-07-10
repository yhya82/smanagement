<?php

namespace App\Livewire\Admin;

use App\Enums\ResultStatus;
use App\Models\ResultEntry;
use App\Services\ResultService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class GradeReviewIndex extends Component
{
    use WithPagination;

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
        $results = ResultEntry::where('status', ResultStatus::Submitted)
            ->with(['student', 'subject', 'schoolClass', 'term'])
            ->oldest()
            ->paginate(15);

        return view('livewire.admin.grade-review-index', ['results' => $results]);
    }
}
