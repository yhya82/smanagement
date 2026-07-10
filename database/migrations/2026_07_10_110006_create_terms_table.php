<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['academic_year_id', 'name']);

            // Same single-active enforcement as academic_years, scoped globally:
            // only one term across the whole system may be active at a time.
            // CASE WHEN (not MySQL's IF()) so this also works on SQLite.
            $table->unsignedTinyInteger('active_marker')
                ->storedAs('CASE WHEN is_active THEN 1 ELSE NULL END')
                ->nullable();
            $table->unique('active_marker');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
