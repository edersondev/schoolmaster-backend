<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_institutional_lookups', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->index();
            $table->unsignedInteger('option_id');
            $table->string('label');
            $table->unsignedTinyInteger('status')->default(1)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['group', 'option_id'], 'school_lookup_group_option_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_institutional_lookups');
    }
};
