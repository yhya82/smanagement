<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_application_id')->constrained('student_applications')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            // One file per document type per application; re-upload updates
            // this row in place (while the application is still pending) and
            // is logged to audit_logs, rather than inserting a new row.
            $table->unique(['student_application_id', 'document_type_id'], 'application_documents_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
