<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_answers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('assessment_response_attempt_id')->constrained('assessment_response_attempts')->cascadeOnDelete();
            $table->foreignId('questionnaire_question_id')->constrained('questionnaire_questions')->cascadeOnDelete();
            $table->string('question_type')->index();
            $table->text('answer_text')->nullable();
            $table->json('answer_metadata')->nullable();
            $table->string('validation_status')->default('accepted')->index();
            $table->string('grading_status')->default('needs_review')->index();
            $table->string('visibility_state')->default('student_safe')->index();
            $table->timestamps();

            $table->unique(['assessment_response_attempt_id', 'questionnaire_question_id'], 'assessment_answer_attempt_question_unique');
            $table->index(['school_id', 'question_type']);
            $table->index(['school_id', 'grading_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
    }
};
