<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'school_id',
    'student_profile_id',
    'academic_period_id',
    'recorded_by_user_id',
    'original_recorded_by_user_id',
    'import_run_id',
    'grade_value',
    'original_grade_value',
    'grade_label',
    'original_grade_label',
    'status',
    'recorded_at',
    'deleted_by_user_id',
    'restored_at',
    'restored_by_user_id',
])]
final class GradeRecord extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (GradeRecord $record): void {
            $record->uuid ??= (string) Str::uuid();
            $record->status ??= 'active';
            $record->recorded_at ??= now();
            $record->original_recorded_by_user_id ??= $record->recorded_by_user_id;
        });
    }

    protected function casts(): array
    {
        return [
            'grade_value' => 'float',
            'original_grade_value' => 'float',
            'recorded_at' => 'datetime',
            'deleted_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
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

    public function originalRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'original_recorded_by_user_id');
    }

    public function importRun(): BelongsTo
    {
        return $this->belongsTo(ImportRun::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(CorrectionRecord::class, 'target_record_id', 'uuid')
            ->where('target_record_type', 'grade')
            ->orderBy('corrected_at');
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'affected_resource_id', 'uuid')
            ->where('affected_resource_type', self::class);
    }
}
