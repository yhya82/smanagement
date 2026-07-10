<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('term_id')->constrained()->restrictOnDelete();
            $table->decimal('average', 5, 2);
            $table->unsignedInteger('position');
            $table->boolean('is_tied')->default(false);
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['student_id', 'term_id']);
            $table->index(['class_id', 'term_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_rankings');
    }
};
