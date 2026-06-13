<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questionnaire_questions', function (Blueprint $table): void {
            $table->json('answer_schema')->nullable();
            $table->json('grading_rule')->nullable();
            $table->json('visibility')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('questionnaire_questions', function (Blueprint $table): void {
            $table->dropColumn(['answer_schema', 'grading_rule', 'visibility']);
        });
    }
};
