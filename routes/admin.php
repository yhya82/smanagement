<?php

use App\Livewire\Admin\AcademicYears\Index as AcademicYearsIndex;
use App\Livewire\Admin\ApplicationReviewIndex;
use App\Livewire\Admin\ApplicationReviewShow;
use App\Livewire\Admin\AttendanceEditRequests\Index as AttendanceEditRequestsIndex;
use App\Livewire\Admin\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\Admin\Classes\AddStudent as ClassAddStudent;
use App\Livewire\Admin\Classes\Import as ClassImport;
use App\Livewire\Admin\Classes\Index as ClassesIndex;
use App\Livewire\Admin\Classes\Subjects as ClassSubjects;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\GradeLevels\Index as GradeLevelsIndex;
use App\Livewire\Admin\GradeReviewIndex;
use App\Livewire\Admin\Periods\Index as PeriodsIndex;
use App\Livewire\Admin\Promotions\Index as PromotionsIndex;
use App\Livewire\Admin\PromotionRules\Index as PromotionRulesIndex;
use App\Livewire\Admin\Rankings\Index as RankingsIndex;
use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Livewire\Admin\Roles\Show as RolesShow;
use App\Livewire\Admin\Settings\Edit as SettingsEdit;
use App\Livewire\Admin\Students\Index as StudentsIndex;
use App\Livewire\Admin\Students\Show as StudentsShow;
use App\Livewire\Admin\Subjects\Index as SubjectsIndex;
use App\Livewire\Admin\Teachers\Index as TeachersIndex;
use App\Livewire\Admin\Teachers\Show as TeachersShow;
use App\Livewire\Admin\Terms\Index as TermsIndex;
use App\Livewire\Admin\Timetable\Show as TimetableShow;
use App\Livewire\Admin\Users\Create as UsersCreate;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Livewire\Admin\Users\Show as UsersShow;
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
    Route::get('classes/{class}/add-student', ClassAddStudent::class)->name('classes.add-student');
    Route::get('classes/{class}/import', ClassImport::class)->name('classes.import');
    Route::get('classes/{class}/timetable', TimetableShow::class)->name('classes.timetable');

    Route::get('periods', PeriodsIndex::class)->name('periods.index');

    Route::get('teachers', TeachersIndex::class)->name('teachers.index');
    Route::get('teachers/{teacher}', TeachersShow::class)->name('teachers.show');

    Route::get('grade-review', GradeReviewIndex::class)->name('grade-review.index');

    Route::get('attendance-edit-requests', AttendanceEditRequestsIndex::class)->name('attendance-edit-requests.index');

    Route::get('rankings', RankingsIndex::class)->name('rankings.index');
    Route::get('promotions', PromotionsIndex::class)->name('promotions.index');
    Route::get('promotion-rules', PromotionRulesIndex::class)->name('promotion-rules.index');

    Route::get('students', StudentsIndex::class)->name('students.index');
    Route::get('students/{student}', StudentsShow::class)->name('students.show');

    Route::get('audit-log', AuditLogsIndex::class)->name('audit-log.index');

    Route::get('users', UsersIndex::class)->name('users.index');
    Route::get('users/create', UsersCreate::class)->name('users.create');
    Route::get('users/{user}', UsersShow::class)->name('users.show');

    Route::get('roles', RolesIndex::class)->name('roles.index');
    Route::get('roles/{role}', RolesShow::class)->name('roles.show');

    Route::get('settings', SettingsEdit::class)->name('settings.edit');
});
