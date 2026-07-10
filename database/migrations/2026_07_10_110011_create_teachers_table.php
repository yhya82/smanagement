<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            // Every teacher is onboarded with a user account up front (status
            // can start 'inactive' until login is granted), so profile_picture
            // only ever needs to live on users, not duplicated here.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_no')->unique();
            $table->enum('status', ['pending', 'active', 'inactive'])->default('pending');
            $table->foreignId('primary_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->date('hire_date');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
