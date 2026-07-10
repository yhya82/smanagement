<?php

namespace App\Livewire\Admin\PromotionRules;

use App\Enums\PromotionCriteriaType;
use App\Models\GradeLevel;
use App\Models\PromotionRule;
use App\Models\SchoolClass;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app-layout')]
class Index extends Component
{
    public bool $showCreateForm = false;

    public string $grade_level_id = '';

    public string $criteria_type = '';

    public string $threshold_value = '';

    public string $target_class_id = '';

    protected function rules(): array
    {
        return [
            'grade_level_id' => ['required', 'exists:grade_levels,id'],
            'criteria_type' => ['required', 'in:rank_threshold,average_threshold'],
            'threshold_value' => ['required', 'numeric', 'min:0'],
            'target_class_id' => ['required', 'exists:classes,id'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', PromotionRule::class);
    }

    public function create(): void
    {
        $this->authorize('create', PromotionRule::class);

        $validated = $this->validate();

        PromotionRule::create([...$validated, 'is_active' => true]);

        $this->reset(['grade_level_id', 'criteria_type', 'threshold_value', 'target_class_id', 'showCreateForm']);
    }

    public function toggleActive(PromotionRule $rule): void
    {
        $this->authorize('update', $rule);

        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function delete(PromotionRule $rule): void
    {
        $this->authorize('delete', $rule);

        $rule->delete();
    }

    public function render()
    {
        return view('livewire.admin.promotion-rules.index', [
            'rules' => PromotionRule::with(['gradeLevel', 'targetClass'])->orderBy('grade_level_id')->get(),
            'gradeLevels' => GradeLevel::orderBy('sort_order')->get(),
            'classes' => SchoolClass::orderBy('name')->get(),
            'criteriaTypes' => PromotionCriteriaType::cases(),
        ]);
    }
}
