<?php

namespace App\Livewire\Teacher;

use App\Enums\ApprovalStatus;
use App\Models\AttendanceEditRequest;
use App\Models\CalendarEvent;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Dashboard extends Component
{
    public function render()
    {
        $teacher = Auth::user()->teacher;

        $assignments = $teacher->subjectAssignments()
            ->where('is_active', true)
            ->with(['subject', 'schoolClass', 'term'])
            ->get();

        $classIds = $assignments->pluck('class_id')->unique();
        $studentCount = $classIds->isEmpty() ? 0 : Student::whereIn('current_class_id', $classIds)->count();

        $pendingEditRequests = AttendanceEditRequest::where('requested_by', Auth::id())
            ->where('status', ApprovalStatus::Pending)
            ->count();

        return view('livewire.teacher.dashboard', [
            'teacher' => $teacher,
            'assignments' => $assignments,
            'studentCount' => $studentCount,
            'pendingEditRequests' => $pendingEditRequests,
            'homeroomClass' => $teacher->homeroomClasses()->first(),
            'upcomingEvents' => CalendarEvent::upcoming(),
        ]);
    }
}
