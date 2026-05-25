<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EnrollmentHistory;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\StudentTransfer;
use App\Models\User;

final class StudentEnrollmentFactory
{
    public static function profile(School $school, User $user, array $attributes = []): StudentProfile
    {
        return StudentProfile::query()->create($attributes + [
            'school_id' => $school->id,
            'user_id' => $user->id,
            'registration_number' => fake()->unique()->numerify('STU-####'),
            'first_name' => 'Test',
            'last_name' => 'Student',
            'status' => 'active',
            'enrolled_at' => now()->toDateString(),
            'status_effective_at' => now()->toDateString(),
        ]);
    }

    public static function history(School $school, StudentProfile $profile, User $actor, array $attributes = []): EnrollmentHistory
    {
        return EnrollmentHistory::query()->create($attributes + [
            'school_id' => $school->id,
            'student_profile_id' => $profile->id,
            'event_type' => 'created',
            'from_status' => null,
            'to_status' => $profile->status,
            'effective_at' => $profile->enrolled_at ?? now()->toDateString(),
            'reason' => 'Created for test.',
            'actor_user_id' => $actor->id,
            'metadata_summary' => [],
        ]);
    }

    public static function transfer(School $school, StudentProfile $profile, User $actor, array $attributes = []): StudentTransfer
    {
        return StudentTransfer::query()->create($attributes + [
            'school_id' => $school->id,
            'student_profile_id' => $profile->id,
            'effective_at' => now()->toDateString(),
            'reason' => 'Transfer for test.',
            'actor_user_id' => $actor->id,
        ]);
    }
}
