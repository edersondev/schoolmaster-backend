<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correction_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('target_record_type')->index();
            $table->uuid('target_record_id');
            $table->json('original_value')->nullable();
            $table->json('new_value');
            $table->text('correction_reason');
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_period_id')->constrained('academic_periods')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->boolean('student_visible')->default(true);
            $table->timestamp('corrected_at');
            $table->timestamps();

            $table->index(['school_id', 'target_record_type', 'target_record_id'], 'corrections_school_target_index');
            $table->index(['school_id', 'student_profile_id', 'academic_period_id'], 'corrections_school_student_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correction_records');
    }
};
