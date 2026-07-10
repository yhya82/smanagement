<?php

namespace App\Livewire\Admin\AcademicYears;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Index extends Component
{
    public bool $showCreateForm = false;

    public string $name = '';

    public string $start_date = '';

    public string $end_date = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:academic_years,name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', AcademicYear::class);

        $validated = $this->validate();

        AcademicYear::create($validated);

        $this->reset(['name', 'start_date', 'end_date', 'showCreateForm']);
    }

    /**
     * Only one academic year may be active at a time (enforced at the DB
     * level via a generated-column unique index) - the old active row has
     * to be deactivated first, in the same transaction, or this collides
     * with that constraint.
     */
    public function activate(AcademicYear $academicYear): void
    {
        $this->authorize('update', $academicYear);

        DB::transaction(function () use ($academicYear) {
            AcademicYear::where('is_active', true)
                ->where('id', '!=', $academicYear->id)
                ->update(['is_active' => false]);

            $academicYear->update(['is_active' => true]);
        });
    }

    public function render()
    {
        return view('livewire.admin.academic-years.index', [
            'academicYears' => AcademicYear::withCount('classes')->latest()->get(),
        ]);
    }
}
