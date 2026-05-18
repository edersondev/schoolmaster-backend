<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'owner_user_id', 'name', 'status'])]
final class TeacherContentFolder extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (TeacherContentFolder $folder): void {
            $folder->uuid ??= (string) Str::uuid();
            $folder->status ??= 'active';
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function contentItems(): HasMany
    {
        return $this->hasMany(TeacherContentItem::class, 'folder_id');
    }
}
