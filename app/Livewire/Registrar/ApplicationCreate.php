<?php

namespace App\Livewire\Registrar;

use App\Enums\Gender;
use App\Services\AdmissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class ApplicationCreate extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $dob = '';

    public string $gender = '';

    /** @var array<int, array{name: string, relationship: string, phone: string, email: string, address: string}> */
    public array $guardians = [
        ['name' => '', 'relationship' => '', 'phone' => '', 'email' => '', 'address' => ''],
    ];

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'dob' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'guardians' => ['required', 'array', 'min:1'],
            'guardians.*.name' => ['required', 'string', 'max:255'],
            'guardians.*.relationship' => ['required', 'string', 'max:255'],
            'guardians.*.phone' => ['required', 'string', 'max:50'],
            'guardians.*.email' => ['nullable', 'email', 'max:255'],
            'guardians.*.address' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function addGuardian(): void
    {
        $this->guardians[] = ['name' => '', 'relationship' => '', 'phone' => '', 'email' => '', 'address' => ''];
    }

    public function removeGuardian(int $index): void
    {
        if (count($this->guardians) <= 1) {
            return;
        }

        unset($this->guardians[$index]);
        $this->guardians = array_values($this->guardians);
    }

    public function save(AdmissionService $admissionService)
    {
        $validated = $this->validate();

        $application = $admissionService->createApplication(
            [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'dob' => $validated['dob'],
                'gender' => $validated['gender'],
            ],
            $validated['guardians'],
            Auth::user()
        );

        session()->flash('status', 'Application created. Upload the required documents to complete it.');

        return $this->redirect(route('registrar.applications.show', $application), navigate: true);
    }

    public function render()
    {
        return view('livewire.registrar.application-create');
    }
}
