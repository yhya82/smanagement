<?php

namespace App\Livewire\Admin\Classes;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\SchoolClass;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Index extends Component
{
    public bool $showCreateForm = false;

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

    public function render()
    {
        return view('livewire.admin.classes.index', [
            'classes' => SchoolClass::with(['gradeLevel', 'academicYear'])
                ->withCount(['students', 'classSubjects'])
                ->latest()
                ->get(),
            'gradeLevels' => GradeLevel::orderBy('sort_order')->get(),
            'academicYears' => AcademicYear::orderBy('name')->get(),
        ]);
    }
}
