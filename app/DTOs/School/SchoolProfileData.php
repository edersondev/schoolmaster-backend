<?php

declare(strict_types=1);

namespace App\DTOs\School;

use Illuminate\Http\UploadedFile;

final readonly class SchoolProfileData
{
    /**
     * @param  array<string, mixed>  $address
     * @param  array<int, int>  $educationLevelIds
     * @param  array<int, int>  $modalityIds
     */
    public function __construct(
        public ?string $inepCode,
        public ?int $status,
        public ?string $name,
        public ?string $tradeName,
        public ?string $legalName,
        public ?string $document,
        public ?string $email,
        public ?string $phone,
        public ?string $website,
        public ?string $description,
        public array $address,
        public ?int $administrativeTypeId,
        public ?int $legalNatureId,
        public ?int $managementTypeId,
        public ?int $pedagogicalApproachId,
        public array $educationLevelIds,
        public array $modalityIds,
        public string $timezone,
        public string $language,
        public string $primaryColor,
        public string $secondaryColor,
        public ?UploadedFile $logoFile,
        public array $submittedFields,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, ?UploadedFile $logoFile = null): self
    {
        return new self(
            inepCode: $data['inep_code'] ?? null,
            status: array_key_exists('status', $data) ? (int) $data['status'] : null,
            name: $data['name'] ?? null,
            tradeName: $data['trade_name'] ?? null,
            legalName: $data['legal_name'] ?? null,
            document: $data['document'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            website: $data['website'] ?? null,
            description: $data['description'] ?? null,
            address: $data['address'] ?? [],
            administrativeTypeId: array_key_exists('administrative_type_id', $data) ? (int) $data['administrative_type_id'] : null,
            legalNatureId: array_key_exists('legal_nature_id', $data) ? (int) $data['legal_nature_id'] : null,
            managementTypeId: array_key_exists('management_type_id', $data) ? (int) $data['management_type_id'] : null,
            pedagogicalApproachId: array_key_exists('pedagogical_approach_id', $data) ? (int) $data['pedagogical_approach_id'] : null,
            educationLevelIds: array_map('intval', $data['education_level_ids'] ?? []),
            modalityIds: array_map('intval', $data['modality_ids'] ?? []),
            timezone: $data['timezone'] ?? 'America/Sao_Paulo',
            language: $data['language'] ?? 'pt-BR',
            primaryColor: $data['primary_color'] ?? '#1D4ED8',
            secondaryColor: $data['secondary_color'] ?? '#F59E0B',
            logoFile: $logoFile,
            submittedFields: array_keys($data),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function schoolAttributes(bool $includeDocument, bool $includeDefaults = false): array
    {
        $attributes = [
            'inep_code' => $this->inepCode,
            'status' => $this->status,
            'name' => $this->name,
            'trade_name' => $this->tradeName,
            'legal_name' => $this->legalName,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'description' => $this->description,
            'administrative_type_id' => $this->administrativeTypeId,
            'legal_nature_id' => $this->legalNatureId,
            'management_type_id' => $this->managementTypeId,
            'pedagogical_approach_id' => $this->pedagogicalApproachId,
            'education_level_ids' => $this->educationLevelIds,
            'modality_ids' => $this->modalityIds,
            'timezone' => $this->timezone,
            'language' => $this->language,
            'primary_color' => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
        ];

        if (! $includeDefaults) {
            foreach (['timezone', 'language', 'primary_color', 'secondary_color'] as $field) {
                if (! in_array($field, $this->submittedFields, true)) {
                    unset($attributes[$field]);
                }
            }
        }

        if ($includeDocument) {
            $attributes['document'] = $this->document;
            $attributes['cnpj'] = $this->document;
        }

        return array_filter($attributes, static fn (mixed $value): bool => $value !== null);
    }
}
