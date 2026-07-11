<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * TimetableService::teacherIsBusyElsewhere() filters by
     * (term_id, day_of_week, period_id) with class_id excluded, not
     * matched - the existing unique index is keyed by class_id first and
     * can't support that lookup at all.
     */
    public function up(): void
    {
        Schema::table('timetable_entries', function (Blueprint $table) {
            $table->index(['term_id', 'day_of_week', 'period_id'], 'timetable_entries_slot_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('timetable_entries', function (Blueprint $table) {
            $table->dropIndex('timetable_entries_slot_lookup_index');
        });
    }
};
