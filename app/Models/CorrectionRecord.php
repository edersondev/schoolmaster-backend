<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'school_id',
    'target_record_type',
    'target_record_id',
    'original_value',
    'new_value',
    'correction_reason',
    'actor_user_id',
    'academic_period_id',
    'student_profile_id',
    'student_visible',
    'corrected_at',
])]
final class CorrectionRecord extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (CorrectionRecord $record): void {
            $record->uuid ??= (string) Str::uuid();
            $record->corrected_at ??= now();
            $record->student_visible ??= true;
        });
    }

    protected function casts(): array
    {
        return [
            'original_value' => 'array',
            'new_value' => 'array',
            'student_visible' => 'boolean',
            'corrected_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }
}
