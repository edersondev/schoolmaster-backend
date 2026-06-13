<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_grading_outcomes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('assessment_response_attempt_id');
            $table->foreignId('assessment_answer_id')->nullable()->constrained('assessment_answers')->cascadeOnDelete();
            $table->foreignId('grader_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('grading_status')->index();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('outcome')->nullable()->index();
            $table->text('feedback_summary')->nullable();
            $table->text('private_grading_note')->nullable();
            $table->timestamp('graded_at');
            $table->timestamps();

            $table->index(['school_id', 'grading_status']);
            $table->index(['assessment_response_attempt_id', 'assessment_answer_id'], 'assessment_grading_attempt_answer_index');
            $table->foreign('assessment_response_attempt_id', 'assessment_grading_attempt_foreign')
                ->references('id')
                ->on('assessment_response_attempts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_grading_outcomes');
    }
};
