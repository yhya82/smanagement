<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gap found while implementing AttendanceService (Phase 6): the table
     * captured a free-text `reason` for the edit but nothing about what the
     * corrected attendance status should actually be, so an approval had
     * nothing to apply to the underlying attendance_records row.
     */
    public function up(): void
    {
        Schema::table('attendance_edit_requests', function (Blueprint $table) {
            $table->enum('requested_status', ['present', 'absent', 'late', 'excused'])
                ->nullable()
                ->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_edit_requests', function (Blueprint $table) {
            $table->dropColumn('requested_status');
        });
    }
};
