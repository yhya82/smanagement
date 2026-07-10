<?php

namespace App\Livewire\Admin\AuditLogs;

use App\Models\AuditLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-layout')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $action = '';

    public function mount(): void
    {
        $this->authorize('viewAny', AuditLog::class);
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($this->action, fn ($query) => $query->where('action', $this->action))
            ->latest()
            ->paginate(25);

        return view('livewire.admin.audit-logs.index', [
            'logs' => $logs,
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
