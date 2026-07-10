<?php

namespace App\Livewire\Student;

use App\Enums\AttendanceStatus;
use App\Enums\ResultStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Dashboard extends Component
{
    public function render()
    {
        $student = Auth::user()->student;

        $approvedResultsCount = $student->resultEntries()->where('status', ResultStatus::Approved)->count();

        $recentAttendance = $student->attendanceRecords()->latest('date')->limit(10)->get();
        $presentCount = $recentAttendance->where('status', AttendanceStatus::Present)->count();

        $unreadNotifications = Auth::user()->notifications()->where('is_read', false)->latest()->limit(5)->get();

        return view('livewire.student.dashboard', [
            'student' => $student,
            'approvedResultsCount' => $approvedResultsCount,
            'recentAttendance' => $recentAttendance,
            'presentCount' => $presentCount,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }
}
