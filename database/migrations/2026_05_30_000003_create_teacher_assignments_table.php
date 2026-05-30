<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_assignments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->string('status')->default('active')->index();
            $table->unsignedTinyInteger('active_assignment_guard')->nullable()->storedAs("case when status = 'active' then 1 else null end");
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->string('deactivation_reason', 500)->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['class_section_id', 'teacher_user_id', 'academic_period_id', 'active_assignment_guard'],
                'teacher_assignments_active_unique'
            );
            $table->index(['school_id', 'class_section_id', 'status'], 'teacher_assignments_school_section_status_index');
            $table->index(['teacher_user_id', 'academic_period_id', 'status'], 'teacher_assignments_teacher_period_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
    }
};
