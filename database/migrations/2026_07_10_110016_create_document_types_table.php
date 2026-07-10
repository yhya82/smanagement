<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            // Per-type upload validation (e.g. birth certificate allows
            // PDF+image, passport photo is image-only) - admin-configurable,
            // enforced at upload time since MySQL CHECK can't join tables.
            $table->json('allowed_mime_types');
            $table->unsignedInteger('max_file_size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
