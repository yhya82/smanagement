<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A teacher can teach multiple classes (teacher_subject_assignments has
     * no such restriction, by design), but can only be the homeroom/"class
     * teacher" of one class at a time. Nullable unique still allows any
     * number of classes with no class teacher assigned - only non-null
     * values are constrained.
     */
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->unique('homeroom_teacher_id');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique(['homeroom_teacher_id']);
        });
    }
};
