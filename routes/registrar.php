<?php

use App\Livewire\Registrar\ApplicationCreate;
use App\Livewire\Registrar\ApplicationIndex;
use App\Livewire\Registrar\ApplicationShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:Registrar'])->prefix('registrar')->name('registrar.')->group(function () {
    Route::get('applications', ApplicationIndex::class)->name('applications.index');
    Route::get('applications/create', ApplicationCreate::class)->name('applications.create');
    Route::get('applications/{application}', ApplicationShow::class)->name('applications.show');
});
