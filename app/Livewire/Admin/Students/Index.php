<?php

namespace App\Livewire\Admin\Students;

use App\Models\SchoolClass;
use App\Models\Student;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $classId = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Student::class);
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $students = Student::query()
            ->with(['currentClass'])
            ->when($this->search, fn ($query) => $query->where(
                fn ($q) => $q->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('student_no', 'like', "%{$this->search}%")
            ))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->classId, fn ($query) => $query->where('current_class_id', $this->classId))
            ->orderBy('last_name')
            ->paginate(15);

        return view('livewire.admin.students.index', [
            'students' => $students,
            'classes' => SchoolClass::orderBy('name')->get(),
        ]);
    }
}
