<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * These were all cascadeOnDelete(), meaning deleting a student's user
     * account would silently wipe their grades, attendance, rankings, and
     * health records (and its own audit trail) with no recovery path -
     * directly against this app's own "status over hard delete" principle
     * (no current UI hard-deletes a student, but nothing at the schema
     * level actually enforced that). restrictOnDelete() makes that
     * deletion fail loudly instead of succeeding silently.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('result_entries', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });

        Schema::table('term_rankings', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });

        Schema::table('student_health_records', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->restrictOnDelete();
        });

        Schema::table('health_record_audits', function (Blueprint $table) {
            $table->dropForeign(['health_record_id']);
            $table->foreign('health_record_id')->references('id')->on('student_health_records')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('health_record_audits', function (Blueprint $table) {
            $table->dropForeign(['health_record_id']);
            $table->foreign('health_record_id')->references('id')->on('student_health_records')->cascadeOnDelete();
        });

        Schema::table('student_health_records', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });

        Schema::table('term_rankings', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });

        Schema::table('result_entries', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
