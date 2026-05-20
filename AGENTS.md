# AGENTS.md

## Repository Purpose

This repository is the Laravel API backend for the SchoolMaster SaaS platform.

The `/specs` directory is the source of truth for:
- product specifications
- business rules
- API contracts
- architecture decisions
- implementation sequencing

Backend work in this repository must follow `/specs` exactly.

## Mandatory Read Order

Before planning, implementing, or reviewing changes:

1. Read `/specs/AGENTS.md`
2. Read the relevant files under `/specs/specs`
3. Read `/specs/api/openapi.yaml`
4. Read any relevant files under `/specs/docs`
5. Read any relevant files under `/specs/decisions`

If there is any conflict, `/specs` wins.

## Non-Negotiable Rules

- Always read `/specs/AGENTS.md` before planning or implementing changes.
- Always follow `/specs/specs`, `/specs/api/openapi.yaml`, `/specs/docs`, and `/specs/decisions`.
- Do not invent business rules.
- Do not create API endpoints that are not defined or planned in the specs.
- If backend behavior changes, update the relevant specs and OpenAPI contract first.
- Keep frontend/backend contracts synchronized through the specs repository.

## Backend Scope

- Backend must be API-only.
- Do not create Blade views for product features.
- Use Laravel native authentication.
- Expose RESTful routes only under `/api/v1`.
- Use MySQL.
- Use UUIDs where applicable for cross-boundary/public identifiers.
- Use tenant-aware design for all school-owned data.

## Multi-Tenancy Rules

- Use the `tenant_id` column strategy for multi-tenancy unless a spec explicitly defines a concrete tenant column for a resource.
- Ensure strict tenant data isolation in controllers, services, policies, queries, and tests.
- Deny cross-tenant access by default.
- Never leak tenant existence or data through validation, authorization, error handling, downloads, or background processing.

If the active specs define a more specific tenant-root rule for a feature, follow the specs exactly.

## Laravel Architecture Rules

- Keep controllers thin.
- Use `FormRequest` classes for validation.
- Use API Resources for responses.
- Use Policies for authorization.
- Use Service classes for business logic.
- Use repositories only when data access is genuinely complex and justified by the codebase or specs.
- Follow PSR-12 and Laravel conventions.
- Prefer explicit types and predictable, maintainable code.

## Expected Backend Structure

The backend should follow this structure:

- `app/Models`
- `app/Http/Controllers/Api/V1`
- `app/Http/Requests`
- `app/Http/Resources`
- `app/Services`
- `app/Policies`
- `database/migrations`
- `database/seeders`
- `routes/api.php`
- `tests/Feature`
- `tests/Unit`

## API Contract Rules

- Implement only routes, payloads, status codes, and error envelopes defined in `/specs/api/openapi.yaml` or explicitly planned in `/specs/specs`.
- Do not add undocumented request fields, response fields, filters, sorting behavior, or authorization semantics.
- If a backend change affects external behavior, update the relevant files in `/specs/specs` and `/specs/api/openapi.yaml` before or alongside backend implementation.
- Treat OpenAPI as the contract that backend behavior must satisfy.

## Testing Rules

- Add tests for critical flows.
- Prefer Feature tests for API behavior, authorization, tenant isolation, and validation.
- Prefer Unit tests for isolated domain and service logic.
- Cover tenant boundaries, authorization, and failure cases for any critical workflow.

## Change Workflow

When implementing a feature or behavior change:

1. Confirm the requirement exists in `/specs`
2. Update the relevant specification files if behavior is changing
3. Update `/specs/api/openapi.yaml` if the contract changes
4. Implement the backend change in this repository
5. Add or update tests for the affected critical flows

Do not start with implementation when the specs or OpenAPI contract are missing or outdated.

## Practical Constraints For Agents

- Respect the specs directory as the source of truth.
- Do not refactor unrelated code.
- Do not introduce frontend-only concerns into the backend repository.
- Do not create product UI artifacts in this repository.
- Prefer maintainable Laravel-native patterns over custom frameworks or unnecessary abstractions.

<!-- SPECKIT START -->
For additional implementation context, read the current plan in the `/specs`
directory before making substantial changes.

Current teacher workflow implementation context:
`specs/specs/004-backend-teacher-workflows/quickstart.md`.
<!-- SPECKIT END -->
