<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_platform_approvals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('support_access_decision_id')->nullable()->constrained('support_access_decisions')->nullOnDelete();
            $table->foreignId('approver_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('support_actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('state')->index();
            $table->string('reason_code')->index();
            $table->string('correlation_id', 120)->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason_code')->nullable();
            $table->timestamps();

            $table->index(['support_actor_user_id', 'school_id', 'state'], 'ipa_actor_school_state_idx');
            $table->index(['school_id', 'state'], 'ipa_school_state_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_platform_approvals');
    }
};
