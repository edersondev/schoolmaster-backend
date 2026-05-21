<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'learning_set_id', 'student_profile_id', 'status', 'assigned_at'])]
final class LearningSetAssignment extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (LearningSetAssignment $assignment): void {
            $assignment->uuid ??= (string) Str::uuid();
            $assignment->status ??= 'active';
            $assignment->assigned_at ??= now();
        });
    }

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    public function learningSet(): BelongsTo
    {
        return $this->belongsTo(LearningSet::class);
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
