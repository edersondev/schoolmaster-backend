<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SchoolInstitutionalLookup;
use Illuminate\Database\Seeder;

final class SchoolInstitutionalLookupSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->options() as $group => $labels) {
            foreach ($labels as $id => $label) {
                SchoolInstitutionalLookup::query()->updateOrCreate(
                    ['group' => $group, 'option_id' => $id],
                    [
                        'label' => $label,
                        'status' => 1,
                        'sort_order' => $id,
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function options(): array
    {
        return [
            SchoolInstitutionalLookup::ADMINISTRATIVE_TYPE => [
                1 => 'Public',
                2 => 'Private',
            ],
            SchoolInstitutionalLookup::LEGAL_NATURE => [
                1 => 'For Profit',
                2 => 'Non Profit',
            ],
            SchoolInstitutionalLookup::MANAGEMENT_TYPE => [
                1 => 'Traditional',
                2 => 'Confessional',
                3 => 'Community',
                4 => 'Philanthropic',
                5 => 'Military',
                6 => 'Corporate',
            ],
            SchoolInstitutionalLookup::PEDAGOGICAL_APPROACH => [
                1 => 'Traditional',
                2 => 'Constructivist',
                3 => 'Montessori',
                4 => 'Waldorf',
                5 => 'Bilingual',
            ],
            SchoolInstitutionalLookup::EDUCATION_LEVEL => [
                1 => 'Early Childhood Education',
                2 => 'Elementary School',
                3 => 'High School',
                4 => 'Technical Education',
                5 => 'Higher Education',
            ],
            SchoolInstitutionalLookup::MODALITY => [
                1 => 'On-site',
                2 => 'Distance Learning',
                3 => 'Hybrid',
            ],
        ];
    }
}
