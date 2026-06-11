<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_access_decisions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedBigInteger('target_school_support_opt_in_id')->nullable()->index();
            $table->unsignedBigInteger('internal_platform_approval_id')->nullable()->index();
            $table->string('reason_code')->index();
            $table->string('purpose', 500);
            $table->string('correlation_id', 120)->index();
            $table->string('state')->index();
            $table->string('support_opt_in_state')->index();
            $table->string('platform_approval_state')->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason_code')->nullable();
            $table->timestamps();

            $table->index(['actor_user_id', 'school_id', 'state']);
            $table->index(['school_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_access_decisions');
    }
};
