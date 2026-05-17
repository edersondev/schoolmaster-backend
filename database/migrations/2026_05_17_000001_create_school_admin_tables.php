<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('planned')->index();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });

        Schema::create('academic_periods', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sequence');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('planned')->index();
            $table->timestamps();

            $table->unique(['academic_year_id', 'sequence']);
            $table->index(['school_id', 'status']);
        });

        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('registration_number')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('current_academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });

        Schema::create('guardians', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('relationship_type');
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });

        Schema::create('guardian_student_profile', function (Blueprint $table): void {
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->primary(['guardian_id', 'student_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student_profile');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('academic_periods');
        Schema::dropIfExists('academic_years');
    }
};
