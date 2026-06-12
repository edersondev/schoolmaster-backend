<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_school_support_opt_ins', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('state')->index();
            $table->string('reason_code')->index();
            $table->string('purpose', 500);
            $table->string('correlation_id', 120)->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revocation_reason_code')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('target_school_support_opt_ins');
    }
};
