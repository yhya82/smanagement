<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('midterm_weight')->default(40)->after('logo_path');
            $table->unsignedTinyInteger('final_weight')->default(60)->after('midterm_weight');
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn(['midterm_weight', 'final_weight']);
        });
    }
};
