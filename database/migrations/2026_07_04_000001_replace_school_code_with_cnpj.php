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
        if (! Schema::hasTable('schools')) {
            return;
        }

        if (! Schema::hasColumn('schools', 'cnpj')) {
            Schema::table('schools', function (Blueprint $table): void {
                $table->string('cnpj', 14)->nullable()->after('name');
            });
        }

        DB::table('schools')
            ->select(['id', 'cnpj'])
            ->orderBy('id')
            ->get()
            ->each(function (object $school): void {
                if ($school->cnpj !== null && $school->cnpj !== '') {
                    return;
                }

                DB::table('schools')
                    ->where('id', $school->id)
                    ->update(['cnpj' => $this->validCnpj((int) $school->id)]);
            });

        if (Schema::hasColumn('schools', 'code')) {
            Schema::table('schools', function (Blueprint $table): void {
                $table->dropUnique('schools_code_unique');
                $table->dropColumn('code');
            });
        }

        if (! $this->hasIndex('schools', 'schools_cnpj_unique')) {
            Schema::table('schools', function (Blueprint $table): void {
                $table->unique('cnpj');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('schools')) {
            return;
        }

        if (! Schema::hasColumn('schools', 'code')) {
            Schema::table('schools', function (Blueprint $table): void {
                $table->string('code')->nullable()->after('name')->unique();
            });
        }

        if (Schema::hasColumn('schools', 'cnpj')) {
            Schema::table('schools', function (Blueprint $table): void {
                if ($this->hasIndex('schools', 'schools_cnpj_unique')) {
                    $table->dropUnique('schools_cnpj_unique');
                }

                $table->dropColumn('cnpj');
            });
        }
    }

    private function validCnpj(int $sequence): string
    {
        $base = str_pad((string) $sequence, 8, '0', STR_PAD_LEFT).'0001';
        $firstDigit = $this->checkDigit($base, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $secondDigit = $this->checkDigit($base.$firstDigit, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return $base.$firstDigit.$secondDigit;
    }

    /**
     * @param  array<int, int>  $weights
     */
    private function checkDigit(string $base, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += ((int) $base[$index]) * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }

    private function hasIndex(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $schemaBuilder = $connection->getSchemaBuilder();

        if (! method_exists($schemaBuilder, 'getIndexes')) {
            return false;
        }

        return collect($schemaBuilder->getIndexes($table))->contains(fn (array $candidate): bool => $candidate['name'] === $index);
    }
};
