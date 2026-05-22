<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'student_profile_id', 'event_type', 'from_status', 'to_status', 'effective_at', 'reason', 'actor_user_id', 'metadata_summary'])]
final class EnrollmentHistory extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (EnrollmentHistory $history): void {
            $history->uuid ??= (string) Str::uuid();
        });

        self::updating(fn (): bool => false);
        self::deleting(fn (): bool => false);
    }

    protected function casts(): array
    {
        return [
            'effective_at' => 'date',
            'metadata_summary' => 'array',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
