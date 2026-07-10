<?php

namespace App\Livewire\Admin;

use App\Enums\ApprovalStatus;
use App\Models\StudentApplication;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class ApplicationReviewIndex extends Component
{
    use WithPagination;

    public function render()
    {
        $applications = StudentApplication::withCount(['guardians', 'documents'])
            ->where('status', ApprovalStatus::Pending)
            ->oldest()
            ->paginate(15);

        return view('livewire.admin.application-review-index', ['applications' => $applications]);
    }
}
