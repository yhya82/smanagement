<?php

namespace App\Livewire\Admin\AttendanceEditRequests;

use App\Models\AttendanceEditRequest;
use App\Services\AttendanceService;
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

    public ?string $actionError = null;

    public function mount(): void
    {
        $this->authorize('viewAny', AttendanceEditRequest::class);
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function approve(int $requestId, AttendanceService $attendanceService): void
    {
        $request = AttendanceEditRequest::findOrFail($requestId);

        $this->authorize('approve', $request);

        $this->actionError = null;

        try {
            $attendanceService->approveEditRequest($request, Auth::user());
        } catch (RuntimeException $e) {
            $this->actionError = $e->getMessage();
        }
    }

    public function reject(int $requestId, AttendanceService $attendanceService): void
    {
        $request = AttendanceEditRequest::findOrFail($requestId);

        $this->authorize('approve', $request);

        $this->actionError = null;

        try {
            $attendanceService->rejectEditRequest($request, Auth::user());
        } catch (RuntimeException $e) {
            $this->actionError = $e->getMessage();
        }
    }

    public function render()
    {
        $requests = AttendanceEditRequest::with(['attendanceRecord.student', 'attendanceRecord.schoolClass', 'requestedBy'])
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.attendance-edit-requests.index', ['requests' => $requests]);
    }
}
