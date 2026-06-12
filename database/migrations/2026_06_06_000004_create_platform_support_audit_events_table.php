<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_support_audit_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->unsignedBigInteger('support_access_decision_id')->nullable()->index('psae_decision_idx');
            $table->unsignedBigInteger('target_school_support_opt_in_id')->nullable()->index('psae_opt_in_idx');
            $table->unsignedBigInteger('internal_platform_approval_id')->nullable()->index('psae_approval_idx');
            $table->string('action')->index();
            $table->string('outcome')->index();
            $table->string('target_type')->nullable()->index();
            $table->string('target_id')->nullable();
            $table->string('correlation_id', 120)->index();
            $table->string('reason_code')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['school_id', 'action', 'outcome'], 'psae_school_action_outcome_idx');

            $table->foreign('support_access_decision_id', 'psae_decision_fk')->references('id')->on('support_access_decisions')->nullOnDelete();
            $table->foreign('target_school_support_opt_in_id', 'psae_opt_in_fk')->references('id')->on('target_school_support_opt_ins')->nullOnDelete();
            $table->foreign('internal_platform_approval_id', 'psae_approval_fk')->references('id')->on('internal_platform_approvals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_support_audit_events');
    }
};
