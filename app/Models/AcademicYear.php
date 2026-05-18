<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'name', 'start_date', 'end_date', 'status'])]
final class AcademicYear extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (AcademicYear $academicYear): void {
            $academicYear->uuid ??= (string) Str::uuid();
            $academicYear->status ??= 'planned';
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AcademicPeriod::class);
    }
}
