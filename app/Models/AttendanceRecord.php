<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'student_profile_id', 'academic_period_id', 'recorded_by_user_id', 'attendance_date', 'attendance_status', 'status'])]
final class AttendanceRecord extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (AttendanceRecord $record): void {
            $record->uuid ??= (string) Str::uuid();
            $record->status ??= 'active';
        });
    }

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
