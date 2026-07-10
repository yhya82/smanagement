<?php

namespace App\Livewire\Student;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class Attendance extends Component
{
    use WithPagination;

    public function render()
    {
        $student = Auth::user()->student;

        $records = $student->attendanceRecords()->latest('date')->paginate(20);

        return view('livewire.student.attendance', ['records' => $records]);
    }
}
