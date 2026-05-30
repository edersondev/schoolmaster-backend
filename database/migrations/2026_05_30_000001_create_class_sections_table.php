<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sections', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->string('code', 80);
            $table->string('name');
            $table->json('course_metadata')->nullable();
            $table->json('classroom_metadata')->nullable();
            $table->json('section_metadata')->nullable();
            $table->json('group_metadata')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('inactive_reason', 500)->nullable();
            $table->date('inactive_effective_at')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'academic_period_id', 'code'], 'class_sections_school_period_code_unique');
            $table->index(['school_id', 'academic_period_id', 'status'], 'class_sections_school_period_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sections');
    }
};
