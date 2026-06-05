<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('domain')->index();
            $table->json('fields');
            $table->json('filters');
            $table->json('grouping');
            $table->json('sorting');
            $table->json('output_formats');
            $table->string('lifecycle_state')->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->string('non_deleted_name')->nullable()->storedAs('case when deleted_at is null then name else null end');

            $table->unique(['school_id', 'non_deleted_name']);
            $table->index(['school_id', 'lifecycle_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_definitions');
    }
};
