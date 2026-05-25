<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'student_profile_id', 'destination_school_id', 'destination_student_profile_id', 'effective_at', 'reason', 'actor_user_id'])]
final class StudentTransfer extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (StudentTransfer $transfer): void {
            $transfer->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return ['effective_at' => 'date'];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function destinationSchool(): BelongsTo
    {
        return $this->belongsTo(School::class, 'destination_school_id');
    }

    public function destinationStudentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'destination_student_profile_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
