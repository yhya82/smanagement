<?php

use App\Livewire\Teacher\Attendance;
use App\Livewire\Teacher\Dashboard;
use App\Livewire\Teacher\Grades;
use App\Livewire\Teacher\Remarks;
use App\Livewire\Teacher\Timetable;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:Teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('attendance/{class}', Attendance::class)->name('attendance');
    Route::get('grades/{class}/{subject}', Grades::class)->name('grades');
    Route::get('remarks/{class}', Remarks::class)->name('remarks');
    Route::get('timetable', Timetable::class)->name('timetable');
});
