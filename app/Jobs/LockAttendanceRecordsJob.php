<?php

namespace App\Jobs;

use App\Models\AttendanceRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SRS §16: attendance is directly editable for 7 days, after which an
 * administrator-approved edit request is required. This is what actually
 * closes that window - once locked_at is set, AttendanceRecordPolicy::update
 * refuses direct edits regardless of who's asking.
 */
class LockAttendanceRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $locked = AttendanceRecord::query()
            ->whereNull('locked_at')
            ->whereDate('date', '<=', now()->subDays(7)->toDateString())
            ->update(['locked_at' => now()]);

        Log::info('Attendance records locked past the 7-day edit window', ['count' => $locked]);
    }

    /**
     * The only place this data-integrity guarantee's actual execution
     * becomes visible at all - if the queue worker isn't running, this
     * never fires either, but at least a failure *while* running doesn't
     * vanish silently.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('LockAttendanceRecordsJob failed', ['exception' => $exception->getMessage()]);
    }
}
