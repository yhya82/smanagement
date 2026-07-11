<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * School-wide period structure (Period 1: 8:00-8:45, etc.) shared by
     * every class's timetable - simplest reasonable default for an MVP;
     * per-grade-level period lengths would be a bigger schema change, not
     * something to preempt without a stated need for it.
     */
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('sort_order');
            $table->timestamps();

            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
