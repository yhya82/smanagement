<?php

namespace App\Livewire\Admin;

use App\Models\DocumentType;
use App\Models\SchoolClass;
use App\Models\StudentApplication;
use App\Services\AdmissionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

#[Layout('components.app-layout')]
class ApplicationReviewShow extends Component
{
    public StudentApplication $application;

    public string $class_id = '';

    public string $rejection_reason = '';

    public ?string $decisionError = null;

    public function mount(StudentApplication $application): void
    {
        $this->authorize('view', $application);

        $this->application = $application;
    }

    /**
     * Surfaces exactly what's blocking approval before the admin even
     * tries - AdmissionService enforces this too, but showing it up front
     * avoids a round-trip just to find out what's missing.
     */
    public function getMissingDocumentLabelsProperty(): array
    {
        return DocumentType::where('is_required', true)
            ->whereDoesntHave(
                'applicationDocuments',
                fn ($query) => $query->where('student_application_id', $this->application->id)
            )
            ->pluck('label')
            ->all();
    }

    public function approve(AdmissionService $admissionService)
    {
        $this->authorize('approve', $this->application);

        $this->validate(['class_id' => ['required', 'exists:classes,id']]);

        try {
            $admissionService->approve(
                $this->application,
                SchoolClass::findOrFail($this->class_id),
                Auth::user()
            );
        } catch (RuntimeException $e) {
            $this->decisionError = $e->getMessage();

            return;
        }

        session()->flash('status', 'Application approved.');

        return $this->redirect(route('admin.applications.index'), navigate: true);
    }

    public function reject(AdmissionService $admissionService)
    {
        $this->authorize('reject', $this->application);

        $this->validate(['rejection_reason' => ['required', 'string', 'max:255']]);

        try {
            $admissionService->reject($this->application, Auth::user(), $this->rejection_reason);
        } catch (RuntimeException $e) {
            $this->decisionError = $e->getMessage();

            return;
        }

        session()->flash('status', 'Application rejected.');

        return $this->redirect(route('admin.applications.index'), navigate: true);
    }

    public function render()
    {
        $this->application->load('guardians', 'documents.documentType');
        $classes = SchoolClass::orderBy('name')->get();

        return view('livewire.admin.application-review-show', ['classes' => $classes]);
    }
}
