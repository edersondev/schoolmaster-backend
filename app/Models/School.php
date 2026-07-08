<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'inep_code',
    'name',
    'trade_name',
    'legal_name',
    'cnpj',
    'document',
    'email',
    'normalized_email',
    'phone',
    'website',
    'description',
    'status',
    'contact_email',
    'contact_phone',
    'administrative_type_id',
    'legal_nature_id',
    'management_type_id',
    'pedagogical_approach_id',
    'education_level_ids',
    'modality_ids',
    'timezone',
    'language',
    'logo_path',
    'primary_color',
    'secondary_color',
])]
#[Hidden(['id'])]
final class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_INACTIVE = 0;

    public const STATUS_ACTIVE = 1;

    protected static function booted(): void
    {
        self::creating(function (School $school): void {
            $school->uuid ??= (string) Str::uuid();
            $school->status ??= self::STATUS_ACTIVE;
            $school->timezone ??= 'America/Sao_Paulo';
            $school->language ??= 'pt-BR';
            $school->primary_color ??= '#1D4ED8';
            $school->secondary_color ??= '#F59E0B';
        });

        self::saving(function (School $school): void {
            if ($school->email !== null) {
                $school->normalized_email = strtolower($school->email);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'administrative_type_id' => 'integer',
            'legal_nature_id' => 'integer',
            'management_type_id' => 'integer',
            'pedagogical_approach_id' => 'integer',
            'education_level_ids' => 'array',
            'modality_ids' => 'array',
            'status' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
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

    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }
}
