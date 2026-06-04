<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('import_type')->index();
            $table->unsignedSmallInteger('row_count');
            $table->unsignedSmallInteger('accepted_row_count')->default(0);
            $table->unsignedSmallInteger('rejected_row_count')->default(0);
            $table->string('status')->index();
            $table->json('error_summary')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'import_type', 'status']);
            $table->index(['school_id', 'created_at']);
        });

        Schema::table('grade_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('grade_records', 'import_run_id')) {
                $table->foreignId('import_run_id')->nullable()->after('original_recorded_by_user_id')->constrained('import_runs')->nullOnDelete();
                $table->index(['school_id', 'import_run_id']);
            }
        });

        Schema::table('attendance_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('attendance_records', 'import_run_id')) {
                $table->foreignId('import_run_id')->nullable()->after('original_recorded_by_user_id')->constrained('import_runs')->nullOnDelete();
                $table->index(['school_id', 'import_run_id']);
            }
        });
    }

    public function down(): void
    {
        foreach (['grade_records', 'attendance_records'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'import_run_id')) {
                    $table->dropConstrainedForeignId('import_run_id');
                }
            });
        }

        Schema::dropIfExists('import_runs');
    }
};
