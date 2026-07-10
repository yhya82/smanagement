<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Livewire\Shared\Notifications;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/notifications', Notifications::class)->name('notifications');

    Route::get(
        '/application-documents/{document}',
        [DocumentController::class, 'stream']
    )->name('application-documents.stream');
});

require __DIR__.'/registrar.php';
require __DIR__.'/admin.php';
require __DIR__.'/teacher.php';
require __DIR__.'/student.php';
