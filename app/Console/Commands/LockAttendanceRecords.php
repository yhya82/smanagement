<?php

namespace App\Console\Commands;

use App\Jobs\LockAttendanceRecordsJob;
use Illuminate\Console\Command;

class LockAttendanceRecords extends Command
{
    protected $signature = 'attendance:lock';

    protected $description = 'Lock attendance records older than 7 days, requiring admin-approved edit requests from here on (SRS §16)';

    public function handle(): void
    {
        LockAttendanceRecordsJob::dispatch();

        $this->info('Attendance lock job dispatched.');
    }
}
