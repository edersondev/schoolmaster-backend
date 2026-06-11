<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'name', 'code', 'status', 'contact_email', 'contact_phone', 'address_summary'])]
#[Hidden(['id'])]
final class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, SoftDeletes;

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

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class);
    }

    public function supportAccessDecisions(): HasMany
    {
        return $this->hasMany(SupportAccessDecision::class);
    }

    public function targetSchoolSupportOptIns(): HasMany
    {
        return $this->hasMany(TargetSchoolSupportOptIn::class);
    }

    public function internalPlatformApprovals(): HasMany
    {
        return $this->hasMany(InternalPlatformApproval::class);
    }

    public function platformSupportAuditEvents(): HasMany
    {
        return $this->hasMany(PlatformSupportAuditEvent::class);
    }
}
