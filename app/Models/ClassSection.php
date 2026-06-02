<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\ClassSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'school_id',
    'academic_period_id',
    'code',
    'name',
    'course_metadata',
    'classroom_metadata',
    'section_metadata',
    'group_metadata',
    'status',
    'inactive_reason',
    'inactive_effective_at',
    'created_by_user_id',
    'updated_by_user_id',
])]
final class ClassSection extends Model
{
    /** @use HasFactory<ClassSectionFactory> */
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (ClassSection $classSection): void {
            $classSection->uuid ??= (string) Str::uuid();
            $classSection->status ??= 'active';
        });
    }

    protected function casts(): array
    {
        return [
            'course_metadata' => 'array',
            'classroom_metadata' => 'array',
            'section_metadata' => 'array',
            'group_metadata' => 'array',
            'inactive_effective_at' => 'date',
        ];
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

    public function rosterMemberships(): HasMany
    {
        return $this->hasMany(RosterMembership::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
