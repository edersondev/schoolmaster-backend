<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->supportsAlterColumn() || $this->statusColumnIsTinyInteger()) {
            return;
        }

        DB::table('schools')
            ->where('status', 'active')
            ->update(['status' => '1']);

        DB::table('schools')
            ->where('status', '<>', '1')
            ->update(['status' => '0']);

        DB::statement('alter table schools modify status tinyint unsigned not null default 1');
    }

    public function down(): void
    {
        if (! $this->supportsAlterColumn() || ! $this->statusColumnIsTinyInteger()) {
            return;
        }

        DB::statement("alter table schools modify status varchar(255) not null default 'active'");

        DB::table('schools')
            ->where('status', '1')
            ->update(['status' => 'active']);

        DB::table('schools')
            ->where('status', '<>', 'active')
            ->update(['status' => 'inactive']);
    }

    private function supportsAlterColumn(): bool
    {
        return in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true);
    }

    private function statusColumnIsTinyInteger(): bool
    {
        $column = DB::selectOne("show columns from schools where Field = 'status'");

        return str_starts_with((string) ($column->Type ?? ''), 'tinyint');
    }
};
