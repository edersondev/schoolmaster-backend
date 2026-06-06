<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_lifecycle_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('report_run_id')->nullable()->constrained('report_runs')->nullOnDelete();
            $table->foreignId('report_definition_id')->nullable()->constrained('report_definitions')->nullOnDelete();
            $table->string('action')->index();
            $table->string('outcome')->index();
            $table->string('target_type')->index();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->uuid('correlation_id')->index();
            $table->string('reason_code');
            $table->json('summary')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['school_id', 'target_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_lifecycle_events');
    }
};
