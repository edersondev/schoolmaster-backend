<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['uuid', 'questionnaire_id', 'question_type', 'prompt', 'options', 'correct_answer', 'sequence'])]
final class QuestionnaireQuestion extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        self::creating(function (QuestionnaireQuestion $question): void {
            $question->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'sequence' => 'integer',
        ];
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }
}
