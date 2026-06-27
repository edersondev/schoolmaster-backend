<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'id',
    'school_id',
    'street',
    'number',
    'complement',
    'neighborhood',
    'city',
    'state',
    'zip_code',
    'country',
    'addressable_type',
    'addressable_id',
])]
final class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        self::creating(function (Address $address): void {
            $address->id ??= (string) Str::uuid();
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
