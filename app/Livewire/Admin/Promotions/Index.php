<?php

namespace App\Livewire\Admin\Promotions;

use App\Enums\StudentStatus;
use App\Models\Promotion;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Services\PromotionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

#[Layout('components.app-layout')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'pending';

    public bool $showEvaluateForm = false;

    public string $evaluate_class_id = '';

    public string $evaluate_term_id = '';

    public ?string $evaluateResult = null;

    public bool $showManualForm = false;

    public string $manual_student_id = '';

    public string $manual_class_id = '';

    public string $manual_term_id = '';

    public ?string $actionError = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Promotion::class);
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function evaluateClass(PromotionService $promotionService): void
    {
        $this->authorize('create', Promotion::class);

        $this->evaluateResult = null;

        $this->validate([
            'evaluate_class_id' => ['required', 'exists:classes,id'],
            'evaluate_term_id' => ['required', 'exists:terms,id'],
        ], [], ['evaluate_class_id' => 'class', 'evaluate_term_id' => 'term']);

        $class = SchoolClass::findOrFail($this->evaluate_class_id);
        $term = Term::findOrFail($this->evaluate_term_id);

        $created = 0;
        $skipped = 0;

        foreach (Student::where('current_class_id', $class->id)->where('status', StudentStatus::Active)->get() as $student) {
            $promotion = $promotionService->evaluate($student, $term, Auth::user());

            $promotion ? $created++ : $skipped++;
        }

        $this->evaluateResult = "Created {$created} pending promotion(s); {$skipped} student(s) had no ranking or didn't meet any rule.";
        $this->reset(['evaluate_class_id', 'evaluate_term_id']);
    }

    public function createManual(PromotionService $promotionService): void
    {
        $this->authorize('create', Promotion::class);

        $this->actionError = null;

        $this->validate([
            'manual_student_id' => ['required', 'exists:students,id'],
            'manual_class_id' => ['required', 'exists:classes,id'],
            'manual_term_id' => ['required', 'exists:terms,id'],
        ], [], ['manual_student_id' => 'student', 'manual_class_id' => 'class', 'manual_term_id' => 'term']);

        $student = Student::findOrFail($this->manual_student_id);
        $class = SchoolClass::findOrFail($this->manual_class_id);
        $term = Term::findOrFail($this->manual_term_id);

        try {
            $promotionService->createManual($student, $class, $term, Auth::user());
        } catch (RuntimeException $e) {
            $this->actionError = $e->getMessage();

            return;
        }

        $this->reset(['manual_student_id', 'manual_class_id', 'manual_term_id', 'showManualForm']);
    }

    public function approve(int $promotionId, PromotionService $promotionService): void
    {
        $promotion = Promotion::findOrFail($promotionId);

        $this->authorize('approve', $promotion);

        $this->actionError = null;

        try {
            $promotionService->approve($promotion, Auth::user());
        } catch (RuntimeException $e) {
            $this->actionError = $e->getMessage();
        }
    }

    public function reject(int $promotionId, PromotionService $promotionService): void
    {
        $promotion = Promotion::findOrFail($promotionId);

        $this->authorize('approve', $promotion);

        $this->actionError = null;

        try {
            $promotionService->reject($promotion, Auth::user());
        } catch (RuntimeException $e) {
            $this->actionError = $e->getMessage();
        }
    }

    public function render()
    {
        $promotions = Promotion::with(['student', 'fromClass', 'toClass', 'term'])
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.promotions.index', [
            'promotions' => $promotions,
            'classes' => SchoolClass::orderBy('name')->get(),
            'terms' => Term::orderBy('name')->get(),
            // Only queried when the manual-promotion panel is actually open -
            // no reason to load every active student in the school on every
            // visit to this page just in case that form gets opened.
            'students' => $this->showManualForm
                ? Student::where('status', StudentStatus::Active)->orderBy('last_name')->get()
                : collect(),
        ]);
    }
}
