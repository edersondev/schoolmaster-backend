<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\DTOs\Reports\ReportDefinitionData;
use App\DTOs\Reports\ReportActorContext;
use App\Enums\Reports\ReportDefinitionState;
use App\Exceptions\ConflictException;
use App\Models\ReportDefinition;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final class ReportDefinitionService
{
    public function __construct(
        private readonly ReportCatalogService $catalog,
        private readonly ReportDefinitionValidationService $validator,
        private readonly ReportAuditService $audit,
    ) {}

    public function list(ReportActorContext $context, array $filters): LengthAwarePaginator
    {
        $this->authorize($context);

        $query = ReportDefinition::query()
            ->with(['school', 'creator', 'updater'])
            ->where('school_id', $context->school->id);

        if ((bool) ($filters['include_deleted'] ?? false)) {
            $query->withTrashed();
        }

        if (isset($filters['lifecycle_state'])) {
            $query->where('lifecycle_state', $filters['lifecycle_state']);
        }

        return $query->orderBy('name')->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function create(ReportActorContext $context, ReportDefinitionData $data): ReportDefinition
    {
        $this->authorize($context);
        $this->validator->validateCatalog($data, $this->catalog->catalog($context));
        $this->assertNameAvailable($context, $data->name);

        $definition = ReportDefinition::query()->create($this->attributes($context->actor, $context->school->id, $data));
        $this->audit->record($context, 'definition_created', 'succeeded', 'report_definition', $definition->id, 'definition_created', reportDefinition: $definition);

        return $definition->load(['school', 'creator', 'updater']);
    }

    public function update(ReportActorContext $context, string $uuid, array $payload): ReportDefinition
    {
        return DB::transaction(function () use ($context, $uuid, $payload): ReportDefinition {
            $definition = $this->find($context, $uuid);
            $this->authorize($context);
            $this->validator->assertCanUpdate($definition, $payload);

            $data = ReportDefinitionData::fromArray($payload + [
                'name' => $definition->name,
                'description' => $definition->description,
                'domain' => $definition->domain,
                'fields' => $definition->fields,
                'filters' => $definition->filters,
                'grouping' => $definition->grouping,
                'sorting' => $definition->sorting,
                'output_formats' => $definition->output_formats,
            ]);

            $this->validator->validateCatalog($data, $this->catalog->catalog($context));

            if ($data->name !== $definition->name) {
                $this->assertNameAvailable($context, $data->name, $definition->id);
            }

            $definition->update($this->attributes($context->actor, $context->school->id, $data, updating: true));
            $definition->increment('version');
            $this->audit->record($context, 'definition_updated', 'succeeded', 'report_definition', $definition->id, 'definition_updated', reportDefinition: $definition);

            return $definition->refresh()->load(['school', 'creator', 'updater']);
        });
    }

    public function activate(ReportActorContext $context, string $uuid): ReportDefinition
    {
        return $this->transition($context, $uuid, ReportDefinitionState::Active, 'definition_activated');
    }

    public function deactivate(ReportActorContext $context, string $uuid): ReportDefinition
    {
        return $this->transition($context, $uuid, ReportDefinitionState::Inactive, 'definition_deactivated');
    }

    public function delete(ReportActorContext $context, string $uuid): ReportDefinition
    {
        $definition = $this->find($context, $uuid);
        $this->authorize($context);
        $definition->update(['lifecycle_state' => ReportDefinitionState::Deleted->value]);
        $definition->delete();
        $this->audit->record($context, 'definition_deleted', 'succeeded', 'report_definition', $definition->id, 'definition_deleted', reportDefinition: $definition);

        return $definition->refresh()->load(['school', 'creator', 'updater']);
    }

    public function restore(ReportActorContext $context, string $uuid): ReportDefinition
    {
        $definition = $this->find($context, $uuid, withTrashed: true);
        $this->authorize($context);
        $this->assertNameAvailable($context, $definition->name, $definition->id);
        $definition->restore();
        $definition->update(['lifecycle_state' => ReportDefinitionState::Inactive->value]);
        $this->audit->record($context, 'definition_restored', 'succeeded', 'report_definition', $definition->id, 'definition_restored', reportDefinition: $definition);

        return $definition->refresh()->load(['school', 'creator', 'updater']);
    }

    public function find(ReportActorContext $context, string $uuid, bool $withTrashed = false): ReportDefinition
    {
        $query = ReportDefinition::query()
            ->where('uuid', $uuid)
            ->where('school_id', $context->school->id);

        if ($withTrashed) {
            $query->withTrashed();
        }

        $definition = $query->first();

        if ($definition === null) {
            throw (new ModelNotFoundException)->setModel(ReportDefinition::class);
        }

        return $definition;
    }

    private function transition(ReportActorContext $context, string $uuid, ReportDefinitionState $state, string $action): ReportDefinition
    {
        $definition = $this->find($context, $uuid);
        $this->authorize($context);

        if ($definition->lifecycle_state === ReportDefinitionState::Deleted) {
            throw new ConflictException('Deleted definitions must be restored before other lifecycle changes.');
        }

        $definition->update(['lifecycle_state' => $state->value]);
        $this->audit->record($context, $action, 'succeeded', 'report_definition', $definition->id, $action, reportDefinition: $definition);

        return $definition->refresh()->load(['school', 'creator', 'updater']);
    }

    private function authorize(ReportActorContext $context): void
    {
        if (! $context->actor->hasSchoolPermission('reports.definitions.manage', $context->school->id)) {
            abort(403, 'The authenticated user lacks permission for this action.');
        }
    }

    private function assertNameAvailable(ReportActorContext $context, string $name, ?int $ignoreId = null): void
    {
        $exists = ReportDefinition::query()
            ->where('school_id', $context->school->id)
            ->where('name', $name)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw new ConflictException('A non-deleted report definition with this name already exists for the school.');
        }
    }

    private function attributes(User $actor, int $schoolId, ReportDefinitionData $data, bool $updating = false): array
    {
        $attributes = [
            'school_id' => $schoolId,
            'updated_by_user_id' => $actor->id,
            'name' => $data->name,
            'description' => $data->description,
            'domain' => $data->domain,
            'fields' => $data->fields,
            'filters' => $data->filters,
            'grouping' => $data->grouping,
            'sorting' => $data->sorting,
            'output_formats' => $data->outputFormats,
        ];

        if (! $updating) {
            $attributes['created_by_user_id'] = $actor->id;
        }

        return $attributes;
    }
}
