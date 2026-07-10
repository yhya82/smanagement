<?php

namespace App\Livewire\Admin\Terms;

use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Index extends Component
{
    public bool $showCreateForm = false;

    public string $academic_year_id = '';

    public string $name = '';

    public string $start_date = '';

    public string $end_date = '';

    protected function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    public function create(): void
    {
        $this->authorize('create', Term::class);

        $validated = $this->validate();

        Term::create($validated);

        $this->reset(['academic_year_id', 'name', 'start_date', 'end_date', 'showCreateForm']);
    }

    /**
     * Only one term across the whole system may be active at a time
     * (schema review §2.2) - deactivate the current one first, in the same
     * transaction, or this collides with the DB-level unique constraint.
     */
    public function activate(Term $term): void
    {
        $this->authorize('update', $term);

        DB::transaction(function () use ($term) {
            Term::where('is_active', true)
                ->where('id', '!=', $term->id)
                ->update(['is_active' => false]);

            $term->update(['is_active' => true]);
        });
    }

    public function render()
    {
        return view('livewire.admin.terms.index', [
            'terms' => Term::with('academicYear')->latest()->get(),
            'academicYears' => AcademicYear::orderBy('name')->get(),
        ]);
    }
}
