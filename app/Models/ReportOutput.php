<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'report_run_id', 'format', 'storage_path', 'generated_at', 'expires_at', 'status'])]
final class ReportOutput extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        self::creating(function (ReportOutput $output): void {
            $output->uuid ??= (string) Str::uuid();
            $output->status ??= 'available';
        });
    }

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function reportRun(): BelongsTo
    {
        return $this->belongsTo(ReportRun::class);
    }

    public function isExpired(?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->status !== 'available' || $this->expires_at->lte($now);
    }
}
