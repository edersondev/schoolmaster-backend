<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('identity_email_key')->storedAs('LOWER(TRIM(email))');
            $table->index('identity_email_key', 'users_identity_email_key_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_identity_email_key_index');
            $table->dropColumn('identity_email_key');
        });
    }
};
