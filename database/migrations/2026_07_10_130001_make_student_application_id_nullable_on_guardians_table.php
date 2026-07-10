<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gap found while building bulk student import (SRS §15): the original
     * design assumed every guardian originates from a student_application
     * (true for the individual Registrar-application flow), but bulk-
     * imported students never have one - there's no application to attach
     * a guardian to. Making this nullable lets a guardian originate from
     * either side (student_application_id for the application flow,
     * student_id directly for bulk import); at least one of the two being
     * set is an application-layer invariant, not enforceable as a DB CHECK
     * across two nullable FKs without raw SQL.
     */
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->foreignId('student_application_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->foreignId('student_application_id')->nullable(false)->change();
        });
    }
};
