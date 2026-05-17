<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSchool
{
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function scopeForSchool(Builder $query, School|int $school): Builder
    {
        $schoolId = $school instanceof School ? $school->id : $school;

        return $query->where($this->getTable().'.school_id', $schoolId);
    }
}
