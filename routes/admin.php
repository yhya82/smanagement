<?php

use App\Livewire\Admin\ApplicationReviewIndex;
use App\Livewire\Admin\ApplicationReviewShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:Administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('applications', ApplicationReviewIndex::class)->name('applications.index');
    Route::get('applications/{application}', ApplicationReviewShow::class)->name('applications.show');
});
