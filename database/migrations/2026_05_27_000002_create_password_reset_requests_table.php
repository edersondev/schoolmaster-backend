<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('account_identifier_hash', 64)->index();
            $table->string('request_ip_hash', 64)->nullable()->index();
            $table->string('token_hash', 64)->nullable()->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->unsignedInteger('request_count')->default(1);
            $table->timestamp('request_window_started_at')->nullable();
            $table->unsignedInteger('failed_completion_count')->default(0);
            $table->timestamp('failed_completion_window_started_at')->nullable();
            $table->timestamp('suppressed_until')->nullable()->index();
            $table->timestamp('delivery_requested_at')->nullable();
            $table->string('delivery_channel', 16)->nullable();
            $table->json('email_delivery_metadata_summary')->nullable();
            $table->timestamps();

            $table->index(['target_user_id', 'school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_requests');
    }
};
