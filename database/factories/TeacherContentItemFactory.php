<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\TeacherContentItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TeacherContentItem>
 */
final class TeacherContentItemFactory extends Factory
{
    protected $model = TeacherContentItem::class;

    public function definition(): array
    {
        $school = School::factory();
        $owner = User::factory();

        return [
            'school_id' => $school,
            'owner_user_id' => $owner,
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'content_type' => 'pdf',
            'declared_content_type' => 'application/pdf',
            'detected_content_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'storage_path' => 'teacher-content/'.Str::uuid().'/content.pdf',
            'scan_status' => 'clean',
            'status' => 'active',
        ];
    }

    public function pendingScan(): self
    {
        return $this->state(['scan_status' => 'pending']);
    }

    public function failedScan(): self
    {
        return $this->state(['scan_status' => 'failed']);
    }

    public function inactive(): self
    {
        return $this->state(['status' => 'inactive']);
    }

    public function deleted(): self
    {
        return $this->state([
            'status' => 'deleted',
            'deleted_at' => now(),
        ]);
    }
}
