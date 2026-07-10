<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            // MySQL has no partial/filtered unique index. This generated column
            // is NULL for every inactive row and 1 only when is_active = true,
            // so a plain unique index on it enforces "at most one active
            // academic year" at the DB level (MySQL unique indexes allow
            // multiple NULLs but only one non-NULL value). CASE WHEN (not
            // MySQL's IF()) is used so this also works on SQLite, which the
            // test suite runs against.
            $table->unsignedTinyInteger('active_marker')
                ->storedAs('CASE WHEN is_active THEN 1 ELSE NULL END')
                ->nullable();
            $table->unique('active_marker');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
