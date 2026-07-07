<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SchoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'inep_code' => $this->inep_code,
            'status' => $this->status === 'active' ? 1 : (int) $this->status,
            'name' => $this->name,
            'trade_name' => $this->trade_name,
            'legal_name' => $this->legal_name,
            'document' => $this->document,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'description' => $this->description,
            'address' => $this->address ? (new AddressResource($this->address))->resolve() : null,
            'administrative_type_id' => $this->administrative_type_id,
            'legal_nature_id' => $this->legal_nature_id,
            'management_type_id' => $this->management_type_id,
            'pedagogical_approach_id' => $this->pedagogical_approach_id,
            'education_level_ids' => $this->education_level_ids ?? [],
            'modality_ids' => $this->modality_ids ?? [],
            'timezone' => $this->timezone ?? 'America/Sao_Paulo',
            'language' => $this->language ?? 'pt-BR',
            'logo_path' => $this->logo_path,
            'primary_color' => $this->primary_color ?? '#1D4ED8',
            'secondary_color' => $this->secondary_color ?? '#F59E0B',
        ];
    }
}
