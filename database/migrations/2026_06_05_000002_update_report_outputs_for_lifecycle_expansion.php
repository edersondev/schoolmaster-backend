<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_outputs', function (Blueprint $table): void {
            if (! Schema::hasColumn('report_outputs', 'availability')) {
                $table->string('availability')->default('available')->after('status')->index();
            }

            if (! Schema::hasColumn('report_outputs', 'failure_reason_code')) {
                $table->string('failure_reason_code')->nullable()->after('availability');
            }
        });
    }

    public function down(): void
    {
        Schema::table('report_outputs', function (Blueprint $table): void {
            foreach (['failure_reason_code', 'availability'] as $column) {
                if (Schema::hasColumn('report_outputs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
