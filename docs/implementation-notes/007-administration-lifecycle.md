# 007 Backend Administration Lifecycle Implementation Notes

## Approved Operation Boundary

This slice is limited to OpenAPI-approved administration lifecycle operations for:

- platform-scoped school detail, update, activate, deactivate, soft delete, and restore
- school-scoped user detail, update, activate, deactivate, soft delete, restore, and selected bulk lifecycle
- school-scoped role detail, update, activate, deactivate, soft delete, restore, and selected bulk lifecycle
- school-scoped academic year detail, update, activate, deactivate, soft delete, restore, and selected bulk lifecycle
- school-scoped academic period detail, update, activate, deactivate, soft delete, restore, and selected bulk lifecycle
- school-scoped guardian detail, update, activate, deactivate, soft delete, restore, and selected bulk lifecycle

School-owned operations require authentication, an active resolved school context, and the corresponding school-scoped administration permission. School lifecycle operations require platform-scoped school administration permission. Platform scope is not an implicit bypass for school-owned administration lifecycle behavior.

## Blocked Scope

The implementation must not expose invitations, password setup/reset, account recovery, lock recovery, token refresh, direct per-user permission assignment, classroom/course/section/roster workflows, teacher corrections, guardian self-service, report lifecycle expansion, report output lifecycle management, platform support-user access to school-owned records, frontend behavior, permanent purge, anonymization, billing, messaging, notifications, or undocumented APIs.

## Progress Log

- T001: Created implementation notes with the approved operation boundary and blocked scope.
- T002: Added administration lifecycle operation paths, operation IDs, parameters, request schemas, response schemas, lifecycle action semantics, dependency conflict responses, and bulk result schemas to `specs/001-schoolmaster-platform/contracts/openapi.yaml`.
- T003: Mirrored the approved administration lifecycle contract behavior in `api/openapi.yaml`.
- T004: Redocly validation passed for `aggregate@v1` and `schoolmaster-platform@v1` using `npx @redocly/cli lint aggregate@v1 schoolmaster-platform@v1`.
- T005: Confirmed mounted OpenAPI operation IDs for school lifecycle (`activateSchool`, `deactivateSchool`, `deleteSchool`, `restoreSchool`), user lifecycle (`getUser`, `updateUser`, `activateUser`, `deactivateUser`, `deleteUser`, `restoreUser`, `bulkLifecycleUsers`), role lifecycle (`getRole`, `updateRole`, `activateRole`, `deactivateRole`, `deleteRole`, `restoreRole`, `bulkLifecycleRoles`), academic year lifecycle (`getAcademicYear`, `updateAcademicYear`, `activateAcademicYear`, `deactivateAcademicYear`, `deleteAcademicYear`, `restoreAcademicYear`, `bulkLifecycleAcademicYears`), academic period lifecycle (`getAcademicPeriod`, `updateAcademicPeriod`, `activateAcademicPeriod`, `deactivateAcademicPeriod`, `deleteAcademicPeriod`, `restoreAcademicPeriod`, `bulkLifecycleAcademicPeriods`), and guardian lifecycle (`getGuardian`, `updateGuardian`, `activateGuardian`, `deactivateGuardian`, `deleteGuardian`, `restoreGuardian`, `bulkLifecycleGuardians`).
- T006: Final backend route inventory exposes only the approved 007 routes for school, user, role, academic year, academic period, and guardian detail/update/lifecycle/bulk lifecycle operations. No invitation, password recovery, classroom, roster, teacher correction, guardian self-service, report lifecycle expansion, frontend, permanent purge, anonymization, billing, messaging, notification, or support-override routes were added.
- T007: Blocked contract gaps remain for account lifecycle, roster models, teacher corrections, guardian self-service, report lifecycle expansion, platform support access, frontend implementation, permanent purge, anonymization, retention management, and additional lifecycle modes.
- T008-T034: Implemented shared lifecycle foundations: soft deletes for school/user/role/academic year/academic period/guardian models, `lifecycle_histories`, lifecycle permissions, resource registry, transition rules, dependency checks, tenant/authorization helpers, immutable lifecycle history, shared outcome resources, policy registration, and route boundaries.
- T035-T063: Implemented User Story 1 detail/update behavior for platform schools and school-owned users, roles, academic years, academic periods, and guardians. Mutable fields are limited by request validators and registry configuration; ownership, UUID, tenant root, scope, and undocumented fields remain immutable.
- T064-T083: Implemented User Story 2 activate, deactivate, soft-delete, and restore behavior with reason/effective-date validation, dependency conflict checks, tenant-scoped lookup-before-forbidden behavior for school-owned resources, lifecycle history writes, and recoverable soft-delete behavior.
- T084-T098: Implemented User Story 3 selected bulk lifecycle behavior for school-owned users, roles, academic years, academic periods, and guardians. Bulk requests are one resource type, one action, same tenant scope, duplicate-free, bounded to 50 records, and all-or-nothing.
- T099-T110: Added cross-cutting response shape, validation, tenant isolation, authorization, blocked operation, and happy-path regression coverage. Final route inventory, OpenAPI validation, syntax, style, focused tests, and full PHPUnit suite are passing.

## Final Operation Inventory

Approved operation IDs implemented:

- Schools: `getSchool`, `updateSchool`, `activateSchool`, `deactivateSchool`, `deleteSchool`, `restoreSchool`
- Users: `getUser`, `updateUser`, `activateUser`, `deactivateUser`, `deleteUser`, `restoreUser`, `bulkLifecycleUsers`
- Roles: `getRole`, `updateRole`, `activateRole`, `deactivateRole`, `deleteRole`, `restoreRole`, `bulkLifecycleRoles`
- Academic years: `getAcademicYear`, `updateAcademicYear`, `activateAcademicYear`, `deactivateAcademicYear`, `deleteAcademicYear`, `restoreAcademicYear`, `bulkLifecycleAcademicYears`
- Academic periods: `getAcademicPeriod`, `updateAcademicPeriod`, `activateAcademicPeriod`, `deactivateAcademicPeriod`, `deleteAcademicPeriod`, `restoreAcademicPeriod`, `bulkLifecycleAcademicPeriods`
- Guardians: `getGuardian`, `updateGuardian`, `activateGuardian`, `deactivateGuardian`, `deleteGuardian`, `restoreGuardian`, `bulkLifecycleGuardians`

## Tenant And Authorization Rules

- School lifecycle operations are platform-scoped and require platform school administration permissions.
- School-owned administration lifecycle operations require an active resolved `X-School-Id` tenant context and school-scoped permissions for the target resource family.
- Platform scope does not bypass school-owned administration lifecycle tenant rules.
- Cross-tenant school-owned resource IDs are resolved through tenant-scoped queries and return not found rather than leaking existence.
- Existing school audit events for school updates and activation/deactivation are preserved alongside lifecycle history.

## Lifecycle Behavior

- `activate` changes eligible records to `active`.
- `deactivate` changes eligible records to `inactive`.
- `delete` performs recoverable soft deletion.
- `restore` restores soft-deleted records while preserving the last lifecycle status.
- Duplicate transitions, missing reason/effective date, invalid effective date, unsupported actions, already-deleted single-record actions, dependency conflicts, and undocumented request fields are rejected.
- Lifecycle history is append-only and records actor, resource, operation, status transition, effective date, reason, and safe metadata.

## Dependency Conflicts

Implemented conflict checks block:

- school deactivation/deletion while active users remain
- user deactivation/deletion while linked to an active student profile
- role deactivation/deletion while assigned to active users
- academic year deactivation/deletion while active periods remain

Academic period and guardian dependency check classes are present and intentionally no-op for currently modeled dependencies.

## Bulk Behavior

- Supported only for users, roles, academic years, academic periods, and guardians.
- Requires `resource_type`, `action`, `record_ids`, `effective_at`, and `reason`.
- Maximum batch size is 50 records.
- Mixed resource type, duplicate IDs, cross-tenant IDs, missing IDs, dependency conflicts, unsupported actions, and authorization failures reject the request without partial writes.

## Validation Results

- OpenAPI: `npx @redocly/cli lint aggregate@v1 schoolmaster-platform@v1` passed for `api/openapi.yaml` and `specs/001-schoolmaster-platform/contracts/openapi.yaml`.
- Routes: `docker exec schoolmaster-backend-app-1 php artisan route:list --path=api/v1` passed and showed 80 API v1 routes.
- Syntax: `find app database routes tests -name '*.php' -print0 | xargs -0 -n1 php -l` passed.
- Style: `docker exec schoolmaster-backend-app-1 ./vendor/bin/pint --test` passed across 412 files.
- Focused 007 tests: `docker exec schoolmaster-backend-app-1 php artisan test tests/Feature/Api/V1/AdministrationLifecycle tests/Unit/Services/AdministrationLifecycle` passed with 33 tests and 89 assertions.
- Full backend tests: `docker exec schoolmaster-backend-app-1 php artisan test` passed with 155 tests and 683 assertions.

## Remaining Blocked Scope

Still blocked for future specs/contracts: invitations, password setup/reset, account recovery, direct per-user permission assignment, classroom/course/section/roster workflows, teacher corrections, guardian self-service, report lifecycle expansion, report output lifecycle management, platform support access to school-owned records, frontend behavior, permanent purge, anonymization, retention management, billing, messaging, notifications, and additional lifecycle modes.
