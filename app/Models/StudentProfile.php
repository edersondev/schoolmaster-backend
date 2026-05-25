<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'user_id', 'registration_number', 'first_name', 'last_name', 'date_of_birth', 'contact_email', 'contact_phone', 'status', 'current_academic_year_id', 'enrolled_at', 'status_effective_at'])]
final class StudentProfile extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (StudentProfile $studentProfile): void {
            $studentProfile->uuid ??= (string) Str::uuid();
            $studentProfile->status ??= 'active';
            $studentProfile->status_effective_at ??= $studentProfile->enrolled_at;
        });
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'enrolled_at' => 'date',
            'status_effective_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'current_academic_year_id');
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class)
            ->using(GuardianAssociation::class)
            ->withPivot(['uuid', 'school_id', 'relationship_type', 'status'])
            ->withTimestamps();
    }

    public function enrollmentHistories(): HasMany
    {
        return $this->hasMany(EnrollmentHistory::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(StudentTransfer::class);
    }

    public function learningSetAssignments(): HasMany
    {
        return $this->hasMany(LearningSetAssignment::class);
    }

    public function gradeRecords(): HasMany
    {
        return $this->hasMany(GradeRecord::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTransferred(): bool
    {
        return $this->status === 'transferred';
    }

    public function fullName(): string
    {
        return trim((string) $this->first_name.' '.(string) $this->last_name);
    }
}
