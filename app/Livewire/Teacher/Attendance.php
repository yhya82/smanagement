<?php

namespace App\Livewire\Teacher;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\AttendanceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Attendance extends Component
{
    public SchoolClass $class;

    public string $date;

    /** @var array<int, string> keyed by student_id */
    public array $statuses = [];

    public ?string $statusMessage = null;

    public function mount(SchoolClass $class): void
    {
        $teacher = Auth::user()->teacher;

        abort_unless($teacher && $teacher->hasAccessToClass($class->id), 403);

        $this->class = $class;
        $this->date = now()->toDateString();
        $this->loadStatuses();
    }

    public function updatedDate(): void
    {
        $this->loadStatuses();
    }

    private function loadStatuses(): void
    {
        $existing = AttendanceRecord::where('class_id', $this->class->id)
            ->where('date', $this->date)
            ->pluck('status', 'student_id');

        $this->statuses = Student::where('current_class_id', $this->class->id)
            ->get()
            ->mapWithKeys(fn (Student $student) => [
                $student->id => $existing[$student->id]?->value ?? AttendanceStatus::Present->value,
            ])
            ->all();
    }

    public function save(AttendanceService $attendanceService): void
    {
        $teacher = Auth::user()->teacher;

        $existingRecords = AttendanceRecord::where('class_id', $this->class->id)
            ->where('date', $this->date)
            ->get()
            ->keyBy('student_id');

        $toMark = [];

        foreach ($this->statuses as $studentId => $status) {
            $existing = $existingRecords->get($studentId);

            if (! $existing) {
                $toMark[$studentId] = AttendanceStatus::from($status);

                continue;
            }

            // Still within the 7-day direct-edit window and the value
            // actually changed - update in place. Locked records are left
            // untouched here; correcting those goes through the edit-request
            // flow instead (not built into this quick-entry screen).
            if ($existing->locked_at === null && $existing->status->value !== $status) {
                $existing->update(['status' => AttendanceStatus::from($status)]);
            }
        }

        if (! empty($toMark)) {
            $attendanceService->mark($this->class, $this->date, $toMark, $teacher);
        }

        $this->statusMessage = 'Attendance saved for '.Carbon::parse($this->date)->format('M j, Y').'.';
        $this->loadStatuses();
    }

    public function render()
    {
        return view('livewire.teacher.attendance', [
            'students' => Student::where('current_class_id', $this->class->id)->orderBy('last_name')->get(),
            'lockedStudentIds' => AttendanceRecord::where('class_id', $this->class->id)
                ->where('date', $this->date)
                ->whereNotNull('locked_at')
                ->pluck('student_id')
                ->all(),
        ]);
    }
}
