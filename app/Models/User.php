<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'name', 'full_name', 'email', 'password', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->uuid ??= (string) Str::uuid();
            $user->status ??= 'active';
            $user->name ??= $user->full_name;
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function guardianUserLinks(): HasMany
    {
        return $this->hasMany(GuardianUserLink::class);
    }

    public function permissions()
    {
        return Permission::query()
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $this->roles()->where('roles.status', 'active')->pluck('roles.id')));
    }

    public function hasPermission(string $code, string $scope): bool
    {
        return $this->roles()
            ->where('roles.status', 'active')
            ->where('roles.scope', $scope)
            ->whereHas('permissions', fn ($query) => $query->where('code', $code)->where('permissions.status', 'active'))
            ->exists();
    }

    public function hasSchoolPermission(string $code, int $schoolId): bool
    {
        return $this->roles()
            ->where('roles.status', 'active')
            ->where('roles.scope', 'school')
            ->where('roles.school_id', $schoolId)
            ->whereHas('permissions', fn (Builder $query) => $query
                ->where('code', $code)
                ->where('scope', 'school')
                ->where('permissions.status', 'active'))
            ->exists();
    }

    public function isPlatformUser(): bool
    {
        return $this->school_id === null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->deleted_at === null;
    }
}
