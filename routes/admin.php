<?php

use App\Livewire\Admin\AcademicYears\Index as AcademicYearsIndex;
use App\Livewire\Admin\ApplicationReviewIndex;
use App\Livewire\Admin\ApplicationReviewShow;
use App\Livewire\Admin\Classes\Index as ClassesIndex;
use App\Livewire\Admin\Classes\Subjects as ClassSubjects;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\GradeLevels\Index as GradeLevelsIndex;
use App\Livewire\Admin\GradeReviewIndex;
use App\Livewire\Admin\Subjects\Index as SubjectsIndex;
use App\Livewire\Admin\Teachers\Index as TeachersIndex;
use App\Livewire\Admin\Teachers\Show as TeachersShow;
use App\Livewire\Admin\Terms\Index as TermsIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:Administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
    Route::get('applications', ApplicationReviewIndex::class)->name('applications.index');
    Route::get('applications/{application}', ApplicationReviewShow::class)->name('applications.show');

    Route::get('academic-years', AcademicYearsIndex::class)->name('academic-years.index');
    Route::get('terms', TermsIndex::class)->name('terms.index');
    Route::get('grade-levels', GradeLevelsIndex::class)->name('grade-levels.index');
    Route::get('subjects', SubjectsIndex::class)->name('subjects.index');
    Route::get('classes', ClassesIndex::class)->name('classes.index');
    Route::get('classes/{class}/subjects', ClassSubjects::class)->name('classes.subjects');

    Route::get('teachers', TeachersIndex::class)->name('teachers.index');
    Route::get('teachers/{teacher}', TeachersShow::class)->name('teachers.show');

    Route::get('grade-review', GradeReviewIndex::class)->name('grade-review.index');
});
