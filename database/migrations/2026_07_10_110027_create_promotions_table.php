<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('to_class_id')->constrained('classes')->restrictOnDelete();
            $table->foreignId('term_id')->constrained()->restrictOnDelete();
            // Nullable: traceability to the rule that fired, if rule-driven;
            // null for manual, admin-initiated promotions.
            $table->foreignId('promotion_rule_id')->nullable()->constrained('promotion_rules')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
