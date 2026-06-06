<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definition_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('report_definition_id')->constrained('report_definitions')->cascadeOnDelete();
            $table->unsignedInteger('definition_version');
            $table->string('domain');
            $table->json('fields');
            $table->json('filters');
            $table->json('grouping');
            $table->json('sorting');
            $table->json('output_formats');
            $table->json('runtime_filters')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'report_definition_id']);
        });

        Schema::table('report_runs', function (Blueprint $table): void {
            $table->foreign('report_definition_id')->references('id')->on('report_definitions')->nullOnDelete();
            $table->foreign('report_definition_snapshot_id')->references('id')->on('report_definition_snapshots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('report_runs', function (Blueprint $table): void {
            $table->dropForeign(['report_definition_snapshot_id']);
            $table->dropForeign(['report_definition_id']);
        });

        Schema::dropIfExists('report_definition_snapshots');
    }
};
