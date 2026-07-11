<?php

namespace App\Livewire\Admin\Teachers;

use App\Models\Teacher;
use App\Services\TeacherOnboardingService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class Index extends Component
{
    use WithPagination;

    public bool $showCreateForm = false;

    public string $name = '';

    public string $email = '';

    public string $hire_date = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'hire_date' => ['required', 'date'],
        ];
    }

    public function create(TeacherOnboardingService $onboardingService): void
    {
        $this->authorize('create', Teacher::class);

        $validated = $this->validate();

        $onboardingService->onboard($validated['name'], $validated['email'], $validated['hire_date']);

        $this->reset(['name', 'email', 'hire_date', 'showCreateForm']);
    }

    public function render()
    {
        return view('livewire.admin.teachers.index', [
            'teachers' => Teacher::with('user')->withCount('subjectAssignments')->latest()->paginate(15),
        ]);
    }
}
