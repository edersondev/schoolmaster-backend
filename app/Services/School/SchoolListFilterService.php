<?php

declare(strict_types=1);

namespace App\Services\School;

use App\DTOs\School\SchoolListFilters;
use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class SchoolListFilterService
{
    /**
     * @param  Builder<School>  $query
     * @return Builder<School>
     */
    public function apply(Builder $query, SchoolListFilters $filters): Builder
    {
        return $query
            ->when($filters->status !== null, fn (Builder $query): Builder => $query->where('status', $filters->status))
            ->when($filters->inepCode !== null, fn (Builder $query): Builder => $query->where('inep_code', $filters->inepCode))
            ->when($filters->document !== null, fn (Builder $query): Builder => $query->where('document', $filters->document))
            ->when($filters->administrativeTypeId !== null, fn (Builder $query): Builder => $query->where('administrative_type_id', $filters->administrativeTypeId))
            ->when($filters->legalNatureId !== null, fn (Builder $query): Builder => $query->where('legal_nature_id', $filters->legalNatureId))
            ->when($filters->managementTypeId !== null, fn (Builder $query): Builder => $query->where('management_type_id', $filters->managementTypeId))
            ->when($filters->pedagogicalApproachId !== null, fn (Builder $query): Builder => $query->where('pedagogical_approach_id', $filters->pedagogicalApproachId))
            ->when($filters->name !== null, fn (Builder $query): Builder => $this->whereContainsNormalized($query, 'name', $filters->name))
            ->when($filters->email !== null, fn (Builder $query): Builder => $this->whereContainsNormalized($query, 'email', $filters->email))
            ->when($filters->city !== null, fn (Builder $query): Builder => $query->whereHas(
                'address',
                fn (Builder $addressQuery): Builder => $this->whereContainsNormalized($addressQuery, 'city', $filters->city),
            ))
            ->when($filters->state !== null, fn (Builder $query): Builder => $query->whereHas(
                'address',
                fn (Builder $addressQuery): Builder => $this->whereContainsNormalized($addressQuery, 'state', $filters->state),
            ));
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    private function whereContainsNormalized(Builder $query, string $column, string $value): Builder
    {
        $escaped = addcslashes(mb_strtolower($value), '\\%_');

        return $query->whereRaw(
            sprintf('LOWER(%s) COLLATE utf8mb4_unicode_ci LIKE ? ESCAPE ?', $query->getQuery()->getGrammar()->wrap($column)),
            ['%'.$escaped.'%', '\\'],
        );
    }
}
