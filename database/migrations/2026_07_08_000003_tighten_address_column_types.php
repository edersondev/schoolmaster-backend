<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $invalidNumber = DB::table('addresses')
            ->whereRaw("number not regexp '^[0-9]+$'")
            ->orWhereRaw('cast(number as unsigned) > 4294967295')
            ->exists();

        if ($invalidNumber) {
            throw new RuntimeException('Cannot narrow addresses.number: existing values must be unsigned integer digits.');
        }

        if (DB::table('addresses')->whereRaw('char_length(state) > 4')->exists()) {
            throw new RuntimeException('Cannot narrow addresses.state: existing values exceed 4 characters.');
        }

        if (DB::table('addresses')->whereRaw('char_length(zip_code) > 12')->exists()) {
            throw new RuntimeException('Cannot narrow addresses.zip_code: existing values exceed 12 characters.');
        }

        DB::statement('alter table addresses modify number int unsigned not null');
        DB::statement('alter table addresses modify state varchar(4) not null');
        DB::statement('alter table addresses modify zip_code varchar(12) not null');
    }

    public function down(): void
    {
        DB::statement('alter table addresses modify number varchar(255) not null');
        DB::statement('alter table addresses modify state varchar(255) not null');
        DB::statement('alter table addresses modify zip_code varchar(255) not null');
    }
};
