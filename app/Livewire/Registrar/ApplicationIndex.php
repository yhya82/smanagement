<?php

namespace App\Livewire\Registrar;

use App\Models\StudentApplication;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class ApplicationIndex extends Component
{
    use WithPagination;

    public function render()
    {
        $applications = StudentApplication::withCount(['guardians', 'documents'])
            ->latest()
            ->paginate(15);

        return view('livewire.registrar.application-index', ['applications' => $applications]);
    }
}
