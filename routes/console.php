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
