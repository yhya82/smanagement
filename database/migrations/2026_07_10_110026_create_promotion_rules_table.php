<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_level_id')->constrained()->cascadeOnDelete();
            $table->enum('criteria_type', ['rank_threshold', 'average_threshold']);
            $table->decimal('threshold_value', 5, 2);
            $table->foreignId('target_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['grade_level_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_rules');
    }
};
