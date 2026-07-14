<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// rankings:compute is deliberately not scheduled here - it runs on an
// explicit admin action when a term closes (SRS §14), not on a fixed
// calendar cadence.
Schedule::command('attendance:lock')->daily();
Schedule::command('applications:purge-documents')->daily();

// Nightly backup of the database + uploaded documents/avatars (see
// config/backup.php), staggered so cleanup runs before a fresh backup is
// taken, and the health check runs after both finish.
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::command('backup:monitor')->daily()->at('02:00');

