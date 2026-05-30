<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\RosterMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'school_id',
    'class_section_id',
    'student_profile_id',
    'academic_period_id',
    'status',
    'effective_start_date',
    'effective_end_date',
    'end_reason',
    'created_by_user_id',
    'ended_by_user_id',
])]
final class RosterMembership extends Model
{
    /** @use HasFactory<RosterMembershipFactory> */
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (RosterMembership $membership): void {
            $membership->uuid ??= (string) Str::uuid();
            $membership->status ??= 'active';
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

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function ender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by_user_id');
    }
}
