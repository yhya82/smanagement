<?php

namespace App\Livewire\Registrar;

use App\Enums\ApprovalStatus;
use App\Models\CalendarEvent;
use App\Models\StudentApplication;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Dashboard extends Component
{
    public function render()
    {
        $counts = [
            'pending' => StudentApplication::where('status', ApprovalStatus::Pending)->count(),
            'approved' => StudentApplication::where('status', ApprovalStatus::Approved)->count(),
            'rejected' => StudentApplication::where('status', ApprovalStatus::Rejected)->count(),
        ];

        $recentApplications = StudentApplication::where('submitted_by', Auth::id())
            ->latest()
            ->limit(5)
            ->get();

        return view('livewire.registrar.dashboard', [
            'counts' => $counts,
            'recentApplications' => $recentApplications,
            'upcomingEvents' => CalendarEvent::upcoming(),
        ]);
    }
}
