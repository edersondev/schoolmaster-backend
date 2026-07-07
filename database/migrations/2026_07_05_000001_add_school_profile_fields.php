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
        Schema::table('schools', function (Blueprint $table): void {
            if (! Schema::hasColumn('schools', 'inep_code')) {
                $table->string('inep_code', 8)->nullable()->after('uuid');
            }

            if (! Schema::hasColumn('schools', 'trade_name')) {
                $table->string('trade_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('schools', 'legal_name')) {
                $table->string('legal_name')->nullable()->after('trade_name');
            }

            if (! Schema::hasColumn('schools', 'document')) {
                $table->string('document', 14)->nullable()->after('legal_name');
            }

            if (! Schema::hasColumn('schools', 'email')) {
                $table->string('email')->nullable()->after('document');
            }

            if (! Schema::hasColumn('schools', 'normalized_email')) {
                $table->string('normalized_email')->nullable()->after('email');
            }

            if (! Schema::hasColumn('schools', 'phone')) {
                $table->string('phone')->nullable()->after('normalized_email');
            }

            if (! Schema::hasColumn('schools', 'website')) {
                $table->string('website')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('schools', 'description')) {
                $table->text('description')->nullable()->after('website');
            }

            if (! Schema::hasColumn('schools', 'administrative_type_id')) {
                $table->unsignedInteger('administrative_type_id')->nullable()->after('description');
            }

            if (! Schema::hasColumn('schools', 'legal_nature_id')) {
                $table->unsignedInteger('legal_nature_id')->nullable()->after('administrative_type_id');
            }

            if (! Schema::hasColumn('schools', 'management_type_id')) {
                $table->unsignedInteger('management_type_id')->nullable()->after('legal_nature_id');
            }

            if (! Schema::hasColumn('schools', 'pedagogical_approach_id')) {
                $table->unsignedInteger('pedagogical_approach_id')->nullable()->after('management_type_id');
            }

            if (! Schema::hasColumn('schools', 'education_level_ids')) {
                $table->json('education_level_ids')->nullable()->after('pedagogical_approach_id');
            }

            if (! Schema::hasColumn('schools', 'modality_ids')) {
                $table->json('modality_ids')->nullable()->after('education_level_ids');
            }

            if (! Schema::hasColumn('schools', 'timezone')) {
                $table->string('timezone')->default('America/Sao_Paulo')->after('modality_ids');
            }

            if (! Schema::hasColumn('schools', 'language')) {
                $table->string('language', 16)->default('pt-BR')->after('timezone');
            }

            if (! Schema::hasColumn('schools', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('language');
            }

            if (! Schema::hasColumn('schools', 'primary_color')) {
                $table->string('primary_color', 7)->default('#1D4ED8')->after('logo_path');
            }

            if (! Schema::hasColumn('schools', 'secondary_color')) {
                $table->string('secondary_color', 7)->default('#F59E0B')->after('primary_color');
            }
        });

        if (Schema::hasColumn('schools', 'cnpj') && Schema::hasColumn('schools', 'document')) {
            DB::table('schools')
                ->whereNull('document')
                ->whereNotNull('cnpj')
                ->update(['document' => DB::raw('cnpj')]);
        }

        Schema::table('schools', function (Blueprint $table): void {
            $table->unique('inep_code', 'schools_inep_code_unique');
            $table->unique('document', 'schools_document_unique');
            $table->unique('normalized_email', 'schools_normalized_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropUnique('schools_inep_code_unique');
            $table->dropUnique('schools_document_unique');
            $table->dropUnique('schools_normalized_email_unique');
            $table->dropColumn([
                'inep_code',
                'trade_name',
                'legal_name',
                'document',
                'email',
                'normalized_email',
                'phone',
                'website',
                'description',
                'administrative_type_id',
                'legal_nature_id',
                'management_type_id',
                'pedagogical_approach_id',
                'education_level_ids',
                'modality_ids',
                'timezone',
                'language',
                'logo_path',
                'primary_color',
                'secondary_color',
            ]);
        });
    }
};
