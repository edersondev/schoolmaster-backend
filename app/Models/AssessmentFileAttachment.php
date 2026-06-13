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
    'assessment_answer_id',
    'original_filename',
    'sanitized_filename',
    'declared_content_type',
    'detected_content_type',
    'file_category',
    'file_size_bytes',
    'storage_path',
    'scan_status',
    'availability_state',
    'uploaded_at',
    'scanned_at',
])]
final class AssessmentFileAttachment extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (AssessmentFileAttachment $attachment): void {
            $attachment->uuid ??= (string) Str::uuid();
            $attachment->scan_status ??= 'pending';
            $attachment->availability_state ??= 'scan_pending';
            $attachment->uploaded_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'uploaded_at' => 'datetime',
            'scanned_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(AssessmentAnswer::class, 'assessment_answer_id');
    }
}
