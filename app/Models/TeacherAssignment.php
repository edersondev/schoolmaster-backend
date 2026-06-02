<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\TeacherAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'school_id',
    'class_section_id',
    'teacher_user_id',
    'academic_period_id',
    'status',
    'effective_start_date',
    'effective_end_date',
    'deactivation_reason',
    'created_by_user_id',
    'updated_by_user_id',
])]
final class TeacherAssignment extends Model
{
    /** @use HasFactory<TeacherAssignmentFactory> */
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (TeacherAssignment $assignment): void {
            $assignment->uuid ??= (string) Str::uuid();
            $assignment->status ??= 'active';
        });
    }

    protected function casts(): array
    {
        return [
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
        ];
    }

    public function classSection(): BelongsTo
    {
        return $this->belongsTo(ClassSection::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
