<?php

namespace App\Livewire\Admin;

use App\Enums\ApprovalStatus;
use App\Enums\StudentStatus;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentApplication;
use App\Models\Teacher;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Dashboard extends Component
{
    public function render()
    {
        $counts = [
            'pending_applications' => StudentApplication::where('status', ApprovalStatus::Pending)->count(),
            'active_students' => Student::where('status', StudentStatus::Active)->count(),
            'teachers' => Teacher::count(),
            'classes' => SchoolClass::count(),
        ];

        $pendingApplications = StudentApplication::where('status', ApprovalStatus::Pending)
            ->oldest()
            ->limit(5)
            ->get();

        return view('livewire.admin.dashboard', [
            'counts' => $counts,
            'pendingApplications' => $pendingApplications,
        ]);
    }
}
