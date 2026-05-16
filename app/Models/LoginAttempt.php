<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['attempt_key_type', 'attempt_key', 'failed_attempt_count', 'window_started_at', 'locked_until'])]
final class LoginAttempt extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'window_started_at' => 'immutable_datetime',
            'locked_until' => 'immutable_datetime',
        ];
    }
}
