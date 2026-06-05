<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_runs', function (Blueprint $table): void {
            if (! Schema::hasColumn('report_runs', 'generation_status')) {
                $table->string('generation_status')->default('requested')->after('status')->index();
            }

            if (! Schema::hasColumn('report_runs', 'source_report_run_id')) {
                $table->foreignId('source_report_run_id')->nullable()->after('outputs_available')->constrained('report_runs')->nullOnDelete();
            }

            if (! Schema::hasColumn('report_runs', 'superseded_by_report_run_id')) {
                $table->foreignId('superseded_by_report_run_id')->nullable()->after('source_report_run_id')->constrained('report_runs')->nullOnDelete();
            }

            if (! Schema::hasColumn('report_runs', 'report_definition_id')) {
                $table->unsignedBigInteger('report_definition_id')->nullable()->after('superseded_by_report_run_id')->index();
            }

            if (! Schema::hasColumn('report_runs', 'report_definition_snapshot_id')) {
                $table->unsignedBigInteger('report_definition_snapshot_id')->nullable()->after('report_definition_id')->index();
            }

            if (! Schema::hasColumn('report_runs', 'failure_reason_code')) {
                $table->string('failure_reason_code')->nullable()->after('report_definition_snapshot_id');
            }

            if (! Schema::hasColumn('report_runs', 'cancellation_reason_code')) {
                $table->string('cancellation_reason_code')->nullable()->after('failure_reason_code');
            }

            if (! Schema::hasColumn('report_runs', 'correlation_id')) {
                $table->uuid('correlation_id')->nullable()->after('cancellation_reason_code')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_runs', function (Blueprint $table): void {
            foreach ([
                'correlation_id',
                'cancellation_reason_code',
                'failure_reason_code',
                'report_definition_snapshot_id',
                'report_definition_id',
                'superseded_by_report_run_id',
                'source_report_run_id',
                'generation_status',
            ] as $column) {
                if (Schema::hasColumn('report_runs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
