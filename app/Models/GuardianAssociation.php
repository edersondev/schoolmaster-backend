<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'guardian_id', 'student_profile_id', 'relationship_type', 'status'])]
final class GuardianAssociation extends Pivot
{
    use BelongsToSchool;

    protected $table = 'guardian_student_profile';

    public $incrementing = false;

    protected static function booted(): void
    {
        self::creating(function (GuardianAssociation $association): void {
            $association->uuid ??= (string) Str::uuid();
            $association->status ??= 'active';
        });
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
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
