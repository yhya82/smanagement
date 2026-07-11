<?php

use App\Livewire\Student\Attendance;
use App\Livewire\Student\Dashboard;
use App\Livewire\Student\Results;
use App\Livewire\Student\Timetable;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:Student'])->prefix('student')->name('student.')->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('results', Results::class)->name('results');
    Route::get('attendance', Attendance::class)->name('attendance');
    Route::get('timetable', Timetable::class)->name('timetable');
});
