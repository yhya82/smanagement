<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_applications', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('dob');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            // Populated only once the application is approved and the student
            // profile (+ user account) is created in the same transaction.
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            // Set by the 90-day retention job once documents/guardians for a
            // rejected application have been purged.
            $table->timestamp('documents_purged_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_applications');
    }
};
