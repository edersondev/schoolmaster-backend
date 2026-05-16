<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'code', 'name', 'scope', 'status'])]
final class Permission extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (Permission $permission): void {
            $permission->uuid ??= (string) Str::uuid();
            $permission->status ??= 'active';
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
