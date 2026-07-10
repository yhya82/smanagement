<?php

namespace App\Livewire\Admin\Students;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentHealthRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Show extends Component
{
    public Student $student;

    public string $newClassId = '';

    public string $allergies = '';

    public string $conditions = '';

    public string $emergencyNotes = '';

    public bool $isConfidential = false;

    public function mount(Student $student): void
    {
        $this->authorize('view', $student);

        $this->student = $student;

        $healthRecord = $student->healthRecord;
        $this->allergies = $healthRecord?->allergies ?? '';
        $this->conditions = $healthRecord?->conditions ?? '';
        $this->emergencyNotes = $healthRecord?->emergency_notes ?? '';
        $this->isConfidential = $healthRecord?->is_confidential ?? false;
    }

    /**
     * A plain administrative move between classes/streams (SRS §7:
     * "manages student records, transfers"), distinct from the
     * rule-driven/approved Promotion workflow - closes out the active
     * enrollment and opens a new one so class history stays intact
     * (SRS §14: historical data is preserved).
     */
    public function transfer(): void
    {
        $this->authorize('update', $this->student);

        $this->validate(['newClassId' => ['required', 'exists:classes,id']]);

        DB::transaction(function () {
            $newClass = SchoolClass::findOrFail($this->newClassId);

            Enrollment::where('student_id', $this->student->id)
                ->where('status', EnrollmentStatus::Active)
                ->update(['status' => EnrollmentStatus::Transferred, 'exit_date' => now()]);

            $this->student->update(['current_class_id' => $newClass->id]);

            Enrollment::create([
                'student_id' => $this->student->id,
                'class_id' => $newClass->id,
                'academic_year_id' => $newClass->academic_year_id,
                'enrollment_date' => now(),
                'status' => EnrollmentStatus::Active,
                'source' => EnrollmentSource::Individual,
            ]);
        });

        $this->reset('newClassId');
        $this->student->refresh();
    }

    public function changeStatus(string $status): void
    {
        $this->authorize('update', $this->student);

        $this->student->update(['status' => StudentStatus::from($status)]);
    }

    public function saveHealthRecord(): void
    {
        $healthRecord = $this->student->healthRecord;

        $this->authorize($healthRecord ? 'update' : 'create', $healthRecord ?? StudentHealthRecord::class);

        $this->validate([
            'allergies' => ['nullable', 'string', 'max:2000'],
            'conditions' => ['nullable', 'string', 'max:2000'],
            'emergencyNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $attributes = [
            'allergies' => $this->allergies ?: null,
            'conditions' => $this->conditions ?: null,
            'emergency_notes' => $this->emergencyNotes ?: null,
            'is_confidential' => $this->isConfidential,
            'updated_by' => Auth::id(),
        ];

        if ($healthRecord) {
            $healthRecord->update($attributes);
        } else {
            StudentHealthRecord::create(['student_id' => $this->student->id, ...$attributes]);
        }

        $this->student->refresh();
    }

    public function render()
    {
        $this->student->load(['guardians', 'healthRecord', 'currentClass']);

        return view('livewire.admin.students.show', [
            'enrollments' => Enrollment::where('student_id', $this->student->id)
                ->with(['schoolClass', 'academicYear'])
                ->latest('enrollment_date')
                ->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
        ]);
    }
}
