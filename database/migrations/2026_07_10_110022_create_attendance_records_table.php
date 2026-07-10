<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'excused']);
            $table->foreignId('marked_by')->constrained('teachers')->restrictOnDelete();
            $table->timestamp('marked_at')->useCurrent();
            // Set by a scheduled job 7 days after `date`; once set, direct
            // edits are blocked and an attendance_edit_requests row is
            // required instead.
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            // Attendance is recorded once per student per day (not per
            // subject/period) - see schema review §2.6 for why a 3-column
            // key including a nullable subject_id would not have actually
            // enforced this.
            $table->unique(['student_id', 'date']);
            $table->index(['class_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
