<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            // v4 fix: guardian info is collected during application (SRS §8),
            // before any students row exists - so guardians key off the
            // application, not the student. student_id is backfilled once
            // the application is approved and the student profile is created.
            $table->foreignId('student_application_id')->constrained('student_applications')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('name');
            $table->string('relationship');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('student_application_id');
            $table->index('student_id');

            // "At least one guardian per application before approval" cannot
            // be expressed as a plain FK (it's the inverse of the normal
            // parent-must-exist direction). Enforced in the application
            // service that transitions student_applications.status to
            // 'approved', not here.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
