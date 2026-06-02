<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateMaterialsTable('teacher_content_items');
        $this->updateMaterialsTable('questionnaires');
    }

    public function down(): void
    {
        foreach (['teacher_content_items', 'questionnaires'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'restored_by_user_id')) {
                    $table->dropColumn('restored_by_user_id');
                }

                if (Schema::hasColumn($tableName, 'restored_at')) {
                    $table->dropColumn('restored_at');
                }

                if (Schema::hasColumn($tableName, 'deleted_by_user_id')) {
                    $table->dropColumn('deleted_by_user_id');
                }

                if (Schema::hasColumn($tableName, 'description')) {
                    $table->dropColumn('description');
                }
            });
        }
    }

    private function updateMaterialsTable(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (! Schema::hasColumn($tableName, 'description')) {
                $table->text('description')->nullable()->after('title');
            }

            if (! Schema::hasColumn($tableName, 'deleted_by_user_id')) {
                $table->foreignId('deleted_by_user_id')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn($tableName, 'restored_at')) {
                $table->timestamp('restored_at')->nullable()->after('deleted_by_user_id');
            }

            if (! Schema::hasColumn($tableName, 'restored_by_user_id')) {
                $table->foreignId('restored_by_user_id')->nullable()->after('restored_at')->constrained('users')->nullOnDelete();
            }
        });
    }
};
