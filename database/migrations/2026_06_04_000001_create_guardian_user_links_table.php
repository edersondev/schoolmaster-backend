<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_user_links', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->string('creation_note', 500)->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('deactivation_reason', 500)->nullable();
            $table->softDeletes();
            $table->string('active_link_unique_key', 191)->nullable();
            $table->timestamps();

            $table->index(['school_id', 'guardian_id', 'status'], 'guardian_user_links_guardian_status_index');
            $table->index(['school_id', 'user_id', 'status'], 'guardian_user_links_user_status_index');
            $table->unique('active_link_unique_key', 'guardian_user_links_active_link_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_user_links');
    }
};
