<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_record_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_record_id')->constrained('student_health_records')->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('health_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_record_audits');
    }
};
