<?php

namespace App\Livewire\Registrar;

use App\Models\ApplicationDocument;
use App\Models\DocumentType;
use App\Models\StudentApplication;
use App\Services\AdmissionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.app-layout')]
class ApplicationShow extends Component
{
    use WithFileUploads;

    public StudentApplication $application;

    /** @var array<int, mixed> keyed by document_type_id */
    public array $uploads = [];

    public ?string $uploadError = null;

    public function mount(StudentApplication $application): void
    {
        $this->authorize('view', $application);

        $this->application = $application;
    }

    public function uploadDocument(int $documentTypeId, AdmissionService $admissionService): void
    {
        $this->authorize('create', ApplicationDocument::class);

        $this->uploadError = null;

        $this->validate([
            "uploads.{$documentTypeId}" => ['required', 'file', 'max:10240'],
        ]);

        $documentType = DocumentType::findOrFail($documentTypeId);

        try {
            $admissionService->uploadDocument(
                $this->application,
                $documentType,
                $this->uploads[$documentTypeId],
                Auth::user()
            );

            unset($this->uploads[$documentTypeId]);
            $this->application->refresh();
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            $this->uploadError = $e->getMessage();
        }
    }

    public function render()
    {
        $documentTypes = DocumentType::where('is_active', true)->get();
        $this->application->load('guardians', 'documents.documentType');

        return view('livewire.registrar.application-show', ['documentTypes' => $documentTypes]);
    }
}
