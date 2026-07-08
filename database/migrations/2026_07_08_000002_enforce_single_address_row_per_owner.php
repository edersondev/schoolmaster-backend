<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deleteDuplicateOwnerRows();

        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropUnique('addresses_one_active_owner_unique');
            $table->unique(['school_id', 'addressable_type', 'addressable_id'], 'addresses_one_owner_unique');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropUnique('addresses_one_owner_unique');
            $table->unique(['school_id', 'addressable_type', 'addressable_id', 'active_owner_marker'], 'addresses_one_active_owner_unique');
        });
    }

    private function deleteDuplicateOwnerRows(): void
    {
        $duplicateOwners = DB::table('addresses')
            ->select('school_id', 'addressable_type', 'addressable_id')
            ->groupBy('school_id', 'addressable_type', 'addressable_id')
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicateOwners as $owner) {
            $ids = DB::table('addresses')
                ->where('school_id', $owner->school_id)
                ->where('addressable_type', $owner->addressable_type)
                ->where('addressable_id', $owner->addressable_id)
                ->orderByRaw('case when deleted_at is null then 0 else 1 end')
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->pluck('id')
                ->all();

            $idsToDelete = array_slice($ids, 1);

            if ($idsToDelete !== []) {
                DB::table('addresses')->whereIn('id', $idsToDelete)->delete();
            }
        }
    }
};
