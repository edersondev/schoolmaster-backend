<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropUnique('permissions_code_unique');
            $table->unique(['code', 'scope'], 'permissions_code_scope_unique');
        });
    }

    public function down(): void
    {
        $duplicateCode = DB::table('permissions')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->value('code');

        if ($duplicateCode !== null) {
            throw new RuntimeException(
                "Cannot restore code-only permission uniqueness while scoped duplicates exist for [{$duplicateCode}].",
            );
        }

        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropUnique('permissions_code_scope_unique');
            $table->unique('code', 'permissions_code_unique');
        });
    }
};
