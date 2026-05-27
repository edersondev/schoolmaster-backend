<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_locks', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lock_type', 32)->index();
            $table->string('status', 24)->default('active')->index();
            $table->text('reason')->nullable();
            $table->timestamp('locked_at')->index();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_locks');
    }
};
