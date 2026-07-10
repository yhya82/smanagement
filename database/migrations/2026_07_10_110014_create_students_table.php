<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            // The user account is created in the same transaction as the
            // student profile (on application approval), status starting
            // 'inactive' until login is granted. This keeps profile_picture
            // single-sourced on users instead of duplicated across
            // students/teachers.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('student_no')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('dob');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('admission_date');
            $table->enum('status', ['active', 'inactive', 'transferred', 'graduated', 'withdrawn'])
                ->default('active');
            $table->foreignId('current_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
