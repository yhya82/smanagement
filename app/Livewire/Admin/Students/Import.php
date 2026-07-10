<?php

namespace App\Livewire\Admin\Students;

use App\Models\Student;
use App\Services\StudentImportService;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.app-layout')]
class Import extends Component
{
    use WithFileUploads;

    public $file;

    public ?int $createdCount = null;

    /** @var list<array{row: int, messages: list<string>}> */
    public array $importErrors = [];

    public ?string $importError = null;

    public function mount(): void
    {
        $this->authorize('create', Student::class);
    }

    public function import(StudentImportService $importService): void
    {
        $this->importError = null;
        $this->createdCount = null;
        $this->importErrors = [];

        $this->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        try {
            $result = $importService->import($this->file->getRealPath());
            $this->createdCount = $result['created'];
            $this->importErrors = $result['errors'];
        } catch (InvalidArgumentException $e) {
            $this->importError = $e->getMessage();
        }

        $this->reset('file');
    }

    public function render()
    {
        return view('livewire.admin.students.import');
    }
}
