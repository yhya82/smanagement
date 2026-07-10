<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->get(
    '/application-documents/{document}',
    [DocumentController::class, 'stream']
)->name('application-documents.stream');
