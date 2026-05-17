<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'scope', 'name', 'status'])]
final class Role extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (Role $role): void {
            $role->uuid ??= (string) Str::uuid();
            $role->status ??= 'active';
        });
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
