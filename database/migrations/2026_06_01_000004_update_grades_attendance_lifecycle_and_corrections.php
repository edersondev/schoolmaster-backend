<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('grade_records', 'original_recorded_by_user_id')) {
                $table->foreignId('original_recorded_by_user_id')->nullable()->after('recorded_by_user_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('grade_records', 'original_grade_value')) {
                $table->decimal('original_grade_value', 5, 2)->nullable()->after('grade_value');
            }

            if (! Schema::hasColumn('grade_records', 'original_grade_label')) {
                $table->string('original_grade_label')->nullable()->after('grade_label');
            }

            $this->addLifecycleColumns($table, 'grade_records');
        });

        Schema::table('attendance_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('attendance_records', 'original_recorded_by_user_id')) {
                $table->foreignId('original_recorded_by_user_id')->nullable()->after('recorded_by_user_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('attendance_records', 'original_attendance_status')) {
                $table->string('original_attendance_status')->nullable()->after('attendance_status');
            }

            $this->addLifecycleColumns($table, 'attendance_records');
        });
    }

    public function down(): void
    {
        foreach ([
            'grade_records' => ['restored_by_user_id', 'restored_at', 'deleted_by_user_id', 'original_grade_label', 'original_grade_value', 'original_recorded_by_user_id'],
            'attendance_records' => ['restored_by_user_id', 'restored_at', 'deleted_by_user_id', 'original_attendance_status', 'original_recorded_by_user_id'],
        ] as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $tableName): void {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function addLifecycleColumns(Blueprint $table, string $tableName): void
    {
        if (! Schema::hasColumn($tableName, 'deleted_by_user_id')) {
            $table->foreignId('deleted_by_user_id')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
        }

        if (! Schema::hasColumn($tableName, 'restored_at')) {
            $table->timestamp('restored_at')->nullable()->after('deleted_by_user_id');
        }

        if (! Schema::hasColumn($tableName, 'restored_by_user_id')) {
            $table->foreignId('restored_by_user_id')->nullable()->after('restored_at')->constrained('users')->nullOnDelete();
        }
    }
};
