<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImportRun;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ImportRun> */
final class ImportRunFactory extends Factory
{
    protected $model = ImportRun::class;

    public function definition(): array
    {
        $school = School::factory()->create();
        $actor = User::factory()->create(['school_id' => $school->id]);

        return [
            'school_id' => $school->id,
            'actor_user_id' => $actor->id,
            'import_type' => 'grade',
            'row_count' => 1,
            'accepted_row_count' => 1,
            'rejected_row_count' => 0,
            'status' => 'accepted',
            'error_summary' => [],
        ];
    }

    public function acceptedGrade(): self
    {
        return $this->state(['import_type' => 'grade', 'status' => 'accepted', 'accepted_row_count' => 1, 'rejected_row_count' => 0, 'error_summary' => []]);
    }

    public function rejectedGrade(): self
    {
        return $this->state(['import_type' => 'grade', 'status' => 'rejected', 'accepted_row_count' => 0, 'rejected_row_count' => 1, 'error_summary' => [['row' => 1, 'code' => 'invalid_row', 'message' => 'Row failed validation.']]]);
    }

    public function acceptedAttendance(): self
    {
        return $this->state(['import_type' => 'attendance', 'status' => 'accepted', 'accepted_row_count' => 1, 'rejected_row_count' => 0, 'error_summary' => []]);
    }

    public function rejectedAttendance(): self
    {
        return $this->state(['import_type' => 'attendance', 'status' => 'rejected', 'accepted_row_count' => 0, 'rejected_row_count' => 1, 'error_summary' => [['row' => 1, 'code' => 'invalid_row', 'message' => 'Row failed validation.']]]);
    }

    public function oversized(): self
    {
        return $this->state(['row_count' => 501, 'accepted_row_count' => 0, 'rejected_row_count' => 501, 'status' => 'rejected']);
    }

    public function duplicateRow(): self
    {
        return $this->state(['status' => 'rejected', 'error_summary' => [['row' => 2, 'code' => 'duplicate_row', 'message' => 'Row duplicates another row in this import.']]]);
    }

    public function crossTenantRow(): self
    {
        return $this->state(['status' => 'rejected', 'error_summary' => [['row' => 1, 'code' => 'invalid_reference', 'message' => 'A referenced record is not valid for the resolved school.']]]);
    }
}
