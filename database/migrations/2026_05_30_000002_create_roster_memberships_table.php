<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_memberships', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_section_id')->constrained('class_sections')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->string('status')->default('active')->index();
            $table->unsignedTinyInteger('active_membership_guard')->nullable()->storedAs("case when status = 'active' then 1 else null end");
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->string('end_reason', 500)->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('ended_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(
                ['class_section_id', 'student_profile_id', 'academic_period_id', 'active_membership_guard'],
                'roster_memberships_active_unique'
            );
            $table->index(['school_id', 'class_section_id', 'status'], 'roster_memberships_school_section_status_index');
            $table->index(['student_profile_id', 'academic_period_id', 'status'], 'roster_memberships_student_period_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_memberships');
    }
};
