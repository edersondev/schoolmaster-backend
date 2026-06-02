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
    'owner_user_id',
    'folder_id',
    'title',
    'description',
    'content_type',
    'declared_content_type',
    'detected_content_type',
    'file_size_bytes',
    'storage_path',
    'scan_status',
    'status',
    'deleted_by_user_id',
    'restored_at',
    'restored_by_user_id',
])]
final class TeacherContentItem extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (TeacherContentItem $content): void {
            $content->uuid ??= (string) Str::uuid();
            $content->scan_status ??= 'pending';
            $content->status ??= 'active';
        });
    }

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'deleted_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(TeacherContentFolder::class, 'folder_id');
    }

    public function learningSetEntries(): HasMany
    {
        return $this->hasMany(LearningSetEntry::class, 'entry_reference_id')
            ->where('entry_type', 'content_item');
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditEvent::class, 'affected_resource_id', 'uuid')
            ->where('affected_resource_type', self::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->scan_status === 'clean';
    }
}
