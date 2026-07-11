<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per (class, term, day, period) actually assigned a subject -
     * an empty slot is simply the absence of a row, not a null-subject row.
     * This keeps "regenerate fills empty slots only" a plain "insert where
     * missing" operation, and avoids pre-creating a full empty grid for
     * every class before it's ever been scheduled.
     *
     * The teacher is deliberately NOT stored here - it's resolved live from
     * teacher_subject_assignments (class_id, subject_id, term_id) whenever
     * an entry is displayed or checked for conflicts, so the timetable
     * never goes stale if a class's teacher assignment changes later.
     */
    public function up(): void
    {
        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('term_id')->constrained();
            $table->foreignId('period_id')->constrained();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
            $table->foreignId('subject_id')->constrained();
            $table->timestamps();

            $table->unique(['class_id', 'term_id', 'day_of_week', 'period_id'], 'timetable_entries_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
    }
};
