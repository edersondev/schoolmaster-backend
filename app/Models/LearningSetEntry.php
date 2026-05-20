<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'school_id', 'learning_set_id', 'entry_type', 'entry_reference_id', 'sequence', 'title'])]
final class LearningSetEntry extends Model
{
    use BelongsToSchool, HasFactory;

    protected static function booted(): void
    {
        self::creating(function (LearningSetEntry $entry): void {
            $entry->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return ['sequence' => 'integer'];
    }

    public function learningSet(): BelongsTo
    {
        return $this->belongsTo(LearningSet::class);
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(TeacherContentItem::class, 'entry_reference_id');
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class, 'entry_reference_id');
    }

    public function resolvedTitle(): string
    {
        if ($this->entry_type === 'content_item' && $this->relationLoaded('contentItem')) {
            return $this->contentItem?->title ?? '';
        }

        if ($this->entry_type === 'questionnaire' && $this->relationLoaded('questionnaire')) {
            return $this->questionnaire?->title ?? '';
        }

        return (string) ($this->getAttribute('title') ?? '');
    }
}
