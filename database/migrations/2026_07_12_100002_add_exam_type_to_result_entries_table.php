<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Midterm and final are graded and approved independently (they happen
     * at different points in the term), so each becomes its own row rather
     * than two columns sharing one draft/submitted/approved lifecycle -
     * existing rows default to 'final' since that's what the pre-split
     * single score represented.
     */
    public function up(): void
    {
        Schema::table('result_entries', function (Blueprint $table) {
            $table->enum('exam_type', ['midterm', 'final'])->default('final')->after('term_id');
        });

        // Add the new unique index before dropping the old one - both start
        // with student_id, and MySQL/InnoDB refuses to drop whichever index
        // is currently propping up the student_id foreign key if it would
        // leave that column with no supporting index at all, even briefly.
        Schema::table('result_entries', function (Blueprint $table) {
            $table->unique(['student_id', 'subject_id', 'term_id', 'exam_type']);
        });

        Schema::table('result_entries', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'subject_id', 'term_id']);
        });
    }

    public function down(): void
    {
        Schema::table('result_entries', function (Blueprint $table) {
            $table->unique(['student_id', 'subject_id', 'term_id']);
        });

        Schema::table('result_entries', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'subject_id', 'term_id', 'exam_type']);
            $table->dropColumn('exam_type');
        });
    }
};
