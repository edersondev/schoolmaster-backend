<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_response_attempts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('questionnaire_id')->constrained('questionnaires')->cascadeOnDelete();
            $table->foreignId('learning_set_id')->constrained('learning_sets')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->string('submission_state')->default('submitted')->index();
            $table->string('grading_status')->default('needs_review')->index();
            $table->decimal('earned_points', 6, 2)->nullable();
            $table->decimal('possible_points', 6, 2)->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(['student_profile_id', 'questionnaire_id', 'learning_set_id'], 'assessment_attempt_student_questionnaire_learning_unique');
            $table->index(['school_id', 'submission_state']);
            $table->index(['school_id', 'grading_status']);
            $table->index(['questionnaire_id', 'learning_set_id'], 'assessment_attempt_questionnaire_learning_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_response_attempts');
    }
};
