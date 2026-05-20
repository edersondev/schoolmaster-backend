<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_content_folders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });

        Schema::create('teacher_content_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('teacher_content_folders')->nullOnDelete();
            $table->string('title');
            $table->string('content_type')->index();
            $table->string('declared_content_type');
            $table->string('detected_content_type');
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('storage_path');
            $table->string('scan_status')->default('pending')->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'scan_status']);
        });

        Schema::create('questionnaires', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
        });

        Schema::create('questionnaire_questions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('questionnaire_id')->constrained('questionnaires')->cascadeOnDelete();
            $table->string('question_type');
            $table->text('prompt');
            $table->json('options')->nullable();
            $table->string('correct_answer')->nullable();
            $table->unsignedInteger('sequence');
            $table->timestamps();

            $table->unique(['questionnaire_id', 'sequence']);
        });

        Schema::create('learning_sets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->string('title');
            $table->timestamp('published_at')->nullable();
            $table->string('status')->default('published')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
        });

        Schema::create('learning_set_entries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('learning_set_id')->constrained('learning_sets')->cascadeOnDelete();
            $table->string('entry_type');
            $table->unsignedBigInteger('entry_reference_id');
            $table->unsignedInteger('sequence');
            $table->timestamps();

            $table->unique(['learning_set_id', 'sequence']);
            $table->index(['school_id', 'entry_type']);
        });

        Schema::create('learning_set_assignments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('learning_set_id')->constrained('learning_sets')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->string('status')->default('active')->index();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->unique(['learning_set_id', 'student_profile_id'], 'lsa_learning_set_student_unique');
            $table->index(['school_id', 'status']);
        });

        Schema::create('grade_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('grade_value', 5, 2);
            $table->string('grade_label')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
            $table->index(['student_profile_id', 'academic_period_id']);
        });

        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->foreignId('recorded_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('attendance_status');
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
            $table->index(['student_profile_id', 'academic_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('grade_records');
        Schema::dropIfExists('learning_set_assignments');
        Schema::dropIfExists('learning_set_entries');
        Schema::dropIfExists('learning_sets');
        Schema::dropIfExists('questionnaire_questions');
        Schema::dropIfExists('questionnaires');
        Schema::dropIfExists('teacher_content_items');
        Schema::dropIfExists('teacher_content_folders');
    }
};
