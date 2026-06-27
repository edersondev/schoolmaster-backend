<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('street');
            $table->string('number');
            $table->string('complement')->nullable();
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state');
            $table->string('zip_code');
            $table->string('country')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->morphs('addressable');
            $table->boolean('active_owner_marker')->nullable()->storedAs('case when `deleted_at` is null then 1 else null end');

            $table->index(['school_id', 'addressable_type']);
            $table->unique(['school_id', 'addressable_type', 'addressable_id', 'active_owner_marker'], 'addresses_one_active_owner_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
