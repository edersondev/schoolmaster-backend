<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Database\Factories\GuardianUserLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'school_id',
    'guardian_id',
    'user_id',
    'created_by_user_id',
    'status',
    'creation_note',
    'deactivated_at',
    'deactivation_reason',
])]
final class GuardianUserLink extends Model
{
    /** @use HasFactory<GuardianUserLinkFactory> */
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (GuardianUserLink $link): void {
            $link->uuid ??= (string) Str::uuid();
            $link->status ??= 'active';
        });

        self::saving(function (GuardianUserLink $link): void {
            $link->active_link_unique_key = $link->status === 'active'
                ? $link->school_id.':'.$link->guardian_id.':'.$link->user_id
                : null;
        });
    }

    protected function casts(): array
    {
        return [
            'deactivated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->deleted_at === null;
    }
}
