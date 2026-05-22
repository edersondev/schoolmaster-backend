<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('first_name')->nullable()->after('registration_number');
            $table->string('last_name')->nullable()->after('first_name');
            $table->date('date_of_birth')->nullable()->after('last_name');
            $table->string('contact_email')->nullable()->after('date_of_birth');
            $table->string('contact_phone', 40)->nullable()->after('contact_email');
            $table->date('enrolled_at')->nullable()->after('current_academic_year_id');
            $table->date('status_effective_at')->nullable()->after('enrolled_at');
            $table->softDeletes();

            $table->unique(['school_id', 'registration_number'], 'student_profiles_school_registration_unique');
            $table->index(['school_id', 'registration_number'], 'student_profiles_school_registration_index');
        });

        Schema::table('guardian_student_profile', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->unique()->after('student_profile_id');
            $table->foreignId('school_id')->nullable()->after('uuid')->constrained('schools')->cascadeOnDelete();
            $table->string('relationship_type')->nullable()->after('school_id');
            $table->string('status')->default('active')->index()->after('relationship_type');
            $table->timestamps();

            $table->index(['school_id', 'status'], 'gsp_school_status_index');
        });

        Schema::create('enrollment_histories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->string('event_type')->index();
            $table->string('from_status')->nullable();
            $table->string('to_status')->index();
            $table->date('effective_at');
            $table->string('reason', 500);
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('metadata_summary')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'event_type']);
            $table->index(['student_profile_id', 'effective_at']);
        });

        Schema::create('student_transfers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('destination_school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('destination_student_profile_id')->nullable()->constrained('student_profiles')->nullOnDelete();
            $table->date('effective_at');
            $table->string('reason', 500);
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'student_profile_id']);
            $table->index(['destination_school_id', 'destination_student_profile_id'], 'student_transfers_destination_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
        Schema::dropIfExists('enrollment_histories');

        Schema::table('guardian_student_profile', function (Blueprint $table): void {
            $table->dropForeign(['school_id']);
            $table->dropIndex('gsp_school_status_index');
            $table->dropColumn(['uuid', 'school_id', 'relationship_type', 'status', 'created_at', 'updated_at']);
        });

        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->dropUnique('student_profiles_school_registration_unique');
            $table->dropIndex('student_profiles_school_registration_index');
            $table->dropColumn([
                'first_name',
                'last_name',
                'date_of_birth',
                'contact_email',
                'contact_phone',
                'enrolled_at',
                'status_effective_at',
                'deleted_at',
            ]);
        });
    }
};
