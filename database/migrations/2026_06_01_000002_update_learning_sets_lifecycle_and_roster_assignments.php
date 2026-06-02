<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_sets', function (Blueprint $table): void {
            if (! Schema::hasColumn('learning_sets', 'description')) {
                $table->text('description')->nullable()->after('title');
            }

            if (! Schema::hasColumn('learning_sets', 'due_at')) {
                $table->timestamp('due_at')->nullable()->after('description');
            }

            if (! Schema::hasColumn('learning_sets', 'deleted_by_user_id')) {
                $table->foreignId('deleted_by_user_id')->nullable()->after('deleted_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('learning_sets', 'restored_at')) {
                $table->timestamp('restored_at')->nullable()->after('deleted_by_user_id');
            }

            if (! Schema::hasColumn('learning_sets', 'restored_by_user_id')) {
                $table->foreignId('restored_by_user_id')->nullable()->after('restored_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('learning_set_assignments', function (Blueprint $table): void {
            if (! Schema::hasColumn('learning_set_assignments', 'assignment_mode')) {
                $table->string('assignment_mode')->default('legacy_direct')->after('learning_set_id')->index();
            }

            if (! Schema::hasColumn('learning_set_assignments', 'class_section_id')) {
                $table->foreignId('class_section_id')->nullable()->after('assignment_mode')->constrained('class_sections')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('learning_set_assignments', function (Blueprint $table): void {
            if (Schema::hasColumn('learning_set_assignments', 'class_section_id')) {
                $table->dropColumn('class_section_id');
            }

            if (Schema::hasColumn('learning_set_assignments', 'assignment_mode')) {
                $table->dropColumn('assignment_mode');
            }
        });

        Schema::table('learning_sets', function (Blueprint $table): void {
            foreach (['restored_by_user_id', 'restored_at', 'deleted_by_user_id', 'due_at', 'description'] as $column) {
                if (Schema::hasColumn('learning_sets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
