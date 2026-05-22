<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'full_name', 'relationship_type', 'contact_email', 'contact_phone', 'status'])]
final class Guardian extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (Guardian $guardian): void {
            $guardian->uuid ??= (string) Str::uuid();
            $guardian->status ??= 'active';
        });
    }

    public function studentProfiles(): BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class)
            ->using(GuardianAssociation::class)
            ->withPivot(['uuid', 'school_id', 'relationship_type', 'status'])
            ->withTimestamps();
    }
}
