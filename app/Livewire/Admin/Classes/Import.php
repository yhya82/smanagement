<?php

namespace App\Livewire\Admin\Classes;

use App\Jobs\ImportStudentsJob;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\StudentImportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.app-layout')]
class Import extends Component
{
    use WithFileUploads;

    public SchoolClass $class;

    public $file;

    public ?string $importError = null;

    public bool $queued = false;

    public function mount(SchoolClass $class): void
    {
        $this->authorize('create', Student::class);

        $this->class = $class;
    }

    /**
     * Validates the file synchronously (wrong columns, wrong extension -
     * fast, cheap checks worth failing immediately on) then hands the
     * actual row-by-row processing to a queued job rather than running a
     * potentially large import inside this request. The upload only
     * exists as a Livewire temporary file for the lifetime of this
     * request, so it's persisted to a real disk first - the job cleans it
     * up once it's done with it.
     */
    public function import(StudentImportService $importService): void
    {
        $this->authorize('create', Student::class);

        $this->importError = null;
        $this->queued = false;

        // Each dispatch is a real bulk-queue job with its own per-row locked
        // transactions (see StudentImportService::generateStudentNumber()) -
        // a handful of rapid repeated clicks (or a scripted request) could
        // otherwise queue several large imports on top of each other with
        // no benefit, just queue backlog.
        $rateLimitKey = 'class-import:'.Auth::id();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $this->importError = 'Too many imports triggered recently - please wait a minute and try again.';

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        $this->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        try {
            $importService->assertValidHeader($this->file->getRealPath());
        } catch (InvalidArgumentException $e) {
            $this->importError = $e->getMessage();

            return;
        }

        $storedPath = $this->file->store('imports', 'local');

        ImportStudentsJob::dispatch($this->class, $storedPath, Auth::user());

        $this->queued = true;
        $this->reset('file');
    }

    public function render()
    {
        return view('livewire.admin.classes.import');
    }
}
