<?php

use App\Livewire\Admin\ApplicationReviewIndex;
use App\Livewire\Admin\ApplicationReviewShow;
use App\Livewire\Admin\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:Administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('applications', ApplicationReviewIndex::class)->name('applications.index');
    Route::get('applications/{application}', ApplicationReviewShow::class)->name('applications.show');
});
