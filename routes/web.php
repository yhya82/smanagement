<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get(
        '/application-documents/{document}',
        [DocumentController::class, 'stream']
    )->name('application-documents.stream');
});

require __DIR__.'/registrar.php';
require __DIR__.'/admin.php';
