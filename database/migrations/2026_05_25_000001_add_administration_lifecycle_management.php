<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['schools', 'users', 'roles', 'academic_years', 'academic_periods', 'guardians'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        Schema::create('lifecycle_histories', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('resource_type')->index();
            $table->unsignedBigInteger('resource_id')->index();
            $table->string('resource_uuid')->index();
            $table->string('operation')->index();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->date('effective_at');
            $table->string('reason', 500);
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('metadata_summary')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'resource_type']);
            $table->index(['resource_type', 'resource_id']);
            $table->index(['operation', 'effective_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lifecycle_histories');

        foreach (['guardians', 'academic_periods', 'academic_years', 'roles', 'users', 'schools'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
