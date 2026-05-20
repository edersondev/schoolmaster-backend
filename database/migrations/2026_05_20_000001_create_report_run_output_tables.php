<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('report_type')->index();
            $table->json('filter_summary');
            $table->json('output_formats');
            $table->string('status')->default('requested')->index();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('output_expires_at')->nullable()->index();
            $table->boolean('outputs_available')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'report_type']);
        });

        Schema::create('report_outputs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('report_run_id')->constrained('report_runs')->cascadeOnDelete();
            $table->string('format')->index();
            $table->string('storage_path');
            $table->timestamp('generated_at');
            $table->timestamp('expires_at')->index();
            $table->string('status')->default('available')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['report_run_id', 'format']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_outputs');
        Schema::dropIfExists('report_runs');
    }
};
