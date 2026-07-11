<?php

namespace App\Livewire\Admin\Classes;

use App\Enums\TeacherStatus;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\Teacher;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class Index extends Component
{
    use WithPagination;

    public bool $showCreateForm = false;

    public ?string $classTeacherError = null;

    public string $grade_level_id = '';

    public string $academic_year_id = '';

    public string $name = '';

    public ?int $capacity = null;

    protected function rules(): array
    {
        return [
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Defaults to the currently active year - the common case by far, so
     * the admin isn't hunting for it in the dropdown every time.
     */
    public function mount(): void
    {
        $this->academic_year_id = (string) (AcademicYear::where('is_active', true)->first()?->id ?? '');
    }

    public function create(): void
    {
        $this->authorize('create', SchoolClass::class);

        $validated = $this->validate();

        SchoolClass::create($validated);

        $activeYearId = $this->academic_year_id;
        $this->reset(['grade_level_id', 'name', 'capacity', 'showCreateForm']);
        $this->academic_year_id = $activeYearId;
    }

    /**
     * "Class teacher" (homeroom) is distinct from a TeacherSubjectAssignment
     * ("class to teach" - a specific subject taught to a class, unrestricted
     * to one class): this is classes.homeroom_teacher_id, which had a column,
     * FK, and model relation already in the schema but no UI anywhere to
     * ever set it. A teacher can only be homeroom of one class at a time
     * (unique constraint) - checked here first for a friendly error instead
     * of letting the DB constraint throw a raw exception.
     */
    public function assignClassTeacher(int $classId, string $teacherId): void
    {
        $class = SchoolClass::findOrFail($classId);

        $this->authorize('update', $class);

        $this->classTeacherError = null;

        if ($teacherId === '') {
            $class->update(['homeroom_teacher_id' => null]);

            return;
        }

        $existingClass = SchoolClass::where('homeroom_teacher_id', $teacherId)
            ->where('id', '!=', $classId)
            ->first();

        if ($existingClass) {
            $this->classTeacherError = "This teacher is already the class teacher for '{$existingClass->name}'.";

            return;
        }

        $class->update(['homeroom_teacher_id' => $teacherId]);
    }

    public function render()
    {
        return view('livewire.admin.classes.index', [
            'classes' => SchoolClass::with(['gradeLevel', 'academicYear', 'homeroomTeacher.user'])
                ->withCount(['students', 'classSubjects'])
                ->latest()
                ->paginate(15),
            'gradeLevels' => GradeLevel::orderBy('sort_order')->get(),
            'academicYears' => AcademicYear::orderBy('name')->get(),
            'teachers' => Teacher::with('user')
                ->where('status', TeacherStatus::Active)
                ->get()
                ->sortBy(fn ($teacher) => $teacher->user->name),
        ]);
    }
}
