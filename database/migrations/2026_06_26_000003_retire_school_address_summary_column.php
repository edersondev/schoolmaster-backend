<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (Schema::hasColumn('schools', 'address_summary')) {
                $table->dropColumn('address_summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            if (! Schema::hasColumn('schools', 'address_summary')) {
                $table->string('address_summary')->nullable();
            }
        });
    }
};
