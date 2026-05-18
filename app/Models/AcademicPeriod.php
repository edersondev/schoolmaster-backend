<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'academic_year_id', 'name', 'sequence', 'start_date', 'end_date', 'status'])]
final class AcademicPeriod extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (AcademicPeriod $academicPeriod): void {
            $academicPeriod->uuid ??= (string) Str::uuid();
            $academicPeriod->status ??= 'planned';
        });
    }

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
