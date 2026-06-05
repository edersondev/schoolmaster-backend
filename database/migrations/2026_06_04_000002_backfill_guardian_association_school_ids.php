<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('guardian_student_profile')
            ->join('guardians', 'guardians.id', '=', 'guardian_student_profile.guardian_id')
            ->join('student_profiles', 'student_profiles.id', '=', 'guardian_student_profile.student_profile_id')
            ->whereNull('guardian_student_profile.school_id')
            ->whereColumn('guardians.school_id', 'student_profiles.school_id')
            ->update([
                'guardian_student_profile.school_id' => DB::raw('guardians.school_id'),
            ]);
    }

    public function down(): void
    {
        // No-op: restoring legacy null school_id values would destroy valid ownership data.
    }
};
