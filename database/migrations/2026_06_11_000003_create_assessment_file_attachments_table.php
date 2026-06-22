<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_file_attachments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('assessment_answer_id')->constrained('assessment_answers')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('sanitized_filename');
            $table->string('declared_content_type');
            $table->string('detected_content_type')->nullable();
            $table->string('file_category')->index();
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('storage_path');
            $table->string('scan_status')->default('pending')->index();
            $table->string('availability_state')->default('scan_pending')->index();
            $table->timestamp('uploaded_at');
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->unique('assessment_answer_id');
            $table->index(['school_id', 'scan_status']);
            $table->index(['school_id', 'availability_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_file_attachments');
    }
};
