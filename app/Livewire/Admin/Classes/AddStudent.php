<?php

namespace App\Livewire\Admin\Classes;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\StudentImportService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('components.app-layout')]
class AddStudent extends Component
{
    public SchoolClass $class;

    public string $first_name = '';

    public string $last_name = '';

    public string $dob = '';

    public string $gender = '';

    public string $guardian_name = '';

    public string $guardian_relationship = '';

    public string $guardian_phone = '';

    public ?string $enrollError = null;

    public bool $enrolled = false;

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'dob' => ['required', 'date'],
            'gender' => ['required', 'in:male,female,other'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_relationship' => ['required', 'string', 'max:255'],
            'guardian_phone' => ['required', 'string', 'max:50'],
        ];
    }

    public function mount(SchoolClass $class): void
    {
        $this->authorize('create', Student::class);

        $this->class = $class;
    }

    public function enroll(StudentImportService $importService): void
    {
        $this->authorize('create', Student::class);

        $this->enrollError = null;
        $this->enrolled = false;

        $validated = $this->validate();

        try {
            $importService->enrollStudent($validated, $this->class);
        } catch (RuntimeException $e) {
            $this->enrollError = $e->getMessage();

            return;
        }

        $this->enrolled = true;
        $this->reset([
            'first_name', 'last_name', 'dob', 'gender',
            'guardian_name', 'guardian_relationship', 'guardian_phone',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.classes.add-student');
    }
}
