<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\ImportRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'school_id',
    'actor_user_id',
    'import_type',
    'row_count',
    'accepted_row_count',
    'rejected_row_count',
    'status',
    'error_summary',
])]
final class ImportRun extends Model
{
    /** @use HasFactory<ImportRunFactory> */
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (ImportRun $run): void {
            $run->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'error_summary' => 'array',
            'row_count' => 'integer',
            'accepted_row_count' => 'integer',
            'rejected_row_count' => 'integer',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function acceptedGrades(): HasMany
    {
        return $this->hasMany(GradeRecord::class);
    }

    public function acceptedAttendance(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'affected_resource_id', 'uuid')
            ->where('affected_resource_type', self::class);
    }
}
