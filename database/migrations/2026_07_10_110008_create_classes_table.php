<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();

            $table->unique(['grade_level_id', 'academic_year_id', 'name']);

            // homeroom_teacher_id is added in a later migration once the
            // teachers table exists: classes <-> teachers is a circular
            // reference (classes.homeroom_teacher_id -> teachers,
            // teachers.primary_class_id -> classes).
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
