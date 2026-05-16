<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'name', 'code', 'status', 'contact_email', 'contact_phone', 'address_summary'])]
#[Hidden(['id'])]
final class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (School $school): void {
            $school->uuid ??= (string) Str::uuid();
            $school->status ??= 'active';
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
