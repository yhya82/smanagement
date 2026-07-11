<?php

namespace App\Livewire\Teacher;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceEditRequest;
use App\Models\AttendanceRecord;
use App\Models\CalendarEvent;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\AttendanceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('components.app-layout')]
class Attendance extends Component
{
    public SchoolClass $class;

    public string $date;

    /** @var array<int, string> keyed by student_id */
    public array $statuses = [];

    public ?string $statusMessage = null;

    public ?int $editingStudentId = null;

    public string $requestedStatus = '';

    public string $reason = '';

    public ?string $editRequestError = null;

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
            ->whereDate('date', $this->date)
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
        if (CalendarEvent::holidayOn($this->date)) {
            $this->statusMessage = null;

            return;
        }

        $teacher = Auth::user()->teacher;

        // The submitted array is a Livewire public property - re-check every
        // key against the class roster at save time rather than trusting
        // that it still only contains the student_ids this component was
        // mounted with (a tampered request could add a foreign student_id).
        $classStudentIds = Student::where('current_class_id', $this->class->id)->pluck('id')->all();

        $existingRecords = AttendanceRecord::where('class_id', $this->class->id)
            ->whereDate('date', $this->date)
            ->get()
            ->keyBy('student_id');

        $toMark = [];

        foreach ($this->statuses as $studentId => $status) {
            if (! in_array($studentId, $classStudentIds, true)) {
                continue;
            }

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

    public function openEditRequest(int $studentId): void
    {
        $this->authorize('create', [AttendanceEditRequest::class, $this->class->id]);

        $this->editingStudentId = $studentId;
        $this->requestedStatus = '';
        $this->reason = '';
        $this->editRequestError = null;
    }

    public function cancelEditRequest(): void
    {
        $this->editingStudentId = null;
    }

    public function submitEditRequest(AttendanceService $attendanceService): void
    {
        $this->authorize('create', [AttendanceEditRequest::class, $this->class->id]);

        $this->editRequestError = null;

        $this->validate([
            'requestedStatus' => ['required', 'in:present,absent,late,excused'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $record = AttendanceRecord::where('class_id', $this->class->id)
            ->whereDate('date', $this->date)
            ->where('student_id', $this->editingStudentId)
            ->firstOrFail();

        try {
            $attendanceService->requestEdit(
                $record,
                Auth::user(),
                AttendanceStatus::from($this->requestedStatus),
                $this->reason
            );
        } catch (RuntimeException $e) {
            $this->editRequestError = $e->getMessage();

            return;
        }

        $this->editingStudentId = null;
        $this->statusMessage = 'Edit request submitted for admin approval.';
    }

    public function render()
    {
        $lockedRecords = AttendanceRecord::where('class_id', $this->class->id)
            ->whereDate('date', $this->date)
            ->whereNotNull('locked_at')
            ->get()
            ->keyBy('student_id');

        return view('livewire.teacher.attendance', [
            'students' => Student::where('current_class_id', $this->class->id)->orderBy('last_name')->get(),
            'lockedRecords' => $lockedRecords,
            'pendingEditRequestRecordIds' => AttendanceEditRequest::whereIn('attendance_id', $lockedRecords->pluck('id'))
                ->where('status', 'pending')
                ->pluck('attendance_id')
                ->all(),
            'holiday' => CalendarEvent::holidayOn($this->date),
        ]);
    }
}
