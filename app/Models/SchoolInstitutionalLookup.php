<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['group', 'option_id', 'label', 'status', 'sort_order'])]
final class SchoolInstitutionalLookup extends Model
{
    public const ADMINISTRATIVE_TYPE = 'administrative_type';
    public const LEGAL_NATURE = 'legal_nature';
    public const MANAGEMENT_TYPE = 'management_type';
    public const PEDAGOGICAL_APPROACH = 'pedagogical_approach';
    public const EDUCATION_LEVEL = 'education_level';
    public const MODALITY = 'modality';

    protected $casts = [
        'option_id' => 'integer',
        'status' => 'integer',
        'sort_order' => 'integer',
    ];
}
