# 006 Backend Student Enrollment Implementation Notes

## Approved Operation Boundary

This slice is limited to the OpenAPI-approved student profile and enrollment operations:

- `listStudentProfiles` - `GET /api/v1/student-profiles`
- `createStudentProfile` - `POST /api/v1/student-profiles`
- `getStudentProfile` - `GET /api/v1/student-profiles/{studentProfileId}`
- `updateStudentProfileStatus` - `PATCH /api/v1/student-profiles/{studentProfileId}/status`
- `transferStudentProfile` - `POST /api/v1/student-profiles/{studentProfileId}/transfer`

All operations require authentication, active resolved school context, and school-scoped student administration permissions. Platform scope is not an implicit bypass for school-scoped student enrollment behavior.

## Blocked Scope

The implementation must not expose frontend behavior, classroom/course/section/roster workflows, teacher assignment workflows, guardian self-service, academic-record correction workflows, report lifecycle changes, bulk import, merge, anonymization, permanent deletion, restore, purge, account lifecycle behavior, billing, messaging, notifications, or undocumented APIs.

## Progress Log

- T001: Created implementation notes with the approved operation boundary and blocked scope.
- T002: Added student profile and enrollment operation paths, operation IDs, parameters, request schemas, response schemas, and error responses to `specs/001-schoolmaster-platform/contracts/openapi.yaml`.
- T003: Mirrored the approved student profile and enrollment operation paths, operation IDs, parameters, request schemas, response schemas, and error responses in `api/openapi.yaml`.
- T004: Redocly validation passed for `aggregate@v1` and `schoolmaster-platform@v1` using `npx @redocly/cli lint aggregate@v1 schoolmaster-platform@v1`.
- T005: Confirmed operation IDs in both mounted OpenAPI contracts: `listStudentProfiles`, `createStudentProfile`, `getStudentProfile`, `updateStudentProfileStatus`, and `transferStudentProfile`.
- T006: Current backend route inventory has no `/api/v1/student-profiles` routes and no student enrollment, transfer, classroom, roster, guardian self-service, correction, bulk import, report-adjacent, or frontend-only routes outside existing documented slices.
- T007: Blocked contract gaps remain for frontend student administration, classroom/course/section/roster, teacher assignment lifecycle workflows, guardian self-service, academic-record correction, report lifecycle changes, bulk import, merge, anonymization, deletion, restore, purge, account lifecycle, and additional filters or sort modes.
- T008: Existing inventory found `StudentProfile` with UUID, `school_id`, `user_id`, registration/status/current year fields, guardian pivot, learning-set/grade/attendance relationships, and active helper. Existing `Guardian`, `School`, `User`, `AcademicPeriod`, `GradeRecord`, `AttendanceRecord`, `LearningSetAssignment`, and `ReportRun` models already carry UUID/status and school ownership where required. Missing pieces are student identity/contact/enrollment dates/status effective date fields, append-only enrollment history, transfer metadata, and school-scoped guardian association metadata on the pivot.
- T009: Added migration `2026_05_21_000001_add_student_profile_enrollment_management.php` for student lifecycle fields, guardian association metadata, enrollment histories, and transfer records.
- T010: Added `student_profiles.view`, `student_profiles.manage`, and `student_transfers.manage` permission definitions.
- T011-T016: Added shared tenant, authorization, list-query, lifecycle, guardian association, and transfer validators.
- T017-T021: Updated `StudentProfile`/`Guardian` relationships and added `EnrollmentHistory`, `GuardianAssociation`, `StudentTransfer`, and `StudentEnrollmentFactory`.
- T022: Added a guarded student profile route group placeholder without operation-specific routes.
- T023: Registered student profile, enrollment history, and transfer policies through Laravel Gate.
- T024-T030: Added US1 feature, unit, tenant, validation, and response-shape tests for create/list/detail behavior.
- T031-T044: Implemented create/list/detail DTOs, requests, resources, policy coverage, services, controller actions, routes, and route/test inventory for `listStudentProfiles`, `createStudentProfile`, and `getStudentProfile`.
- T045-T050: Added US2 lifecycle status, validation, history preservation, self-view access, unit, and response-shape tests.
- T051-T058: Implemented lifecycle status DTO, request, resource, policy coverage, service, controller action, route, transition matrix, and history behavior for `updateStudentProfileStatus`.
- T059-T064: Added US3 transfer success, destination linking, validation, tenant isolation, unit, and response-shape tests.
- T065-T072: Implemented transfer DTO, request, resource, policy coverage, service, controller action, route, destination permission behavior, tenant isolation guarantees, and no-copy behavior for `transferStudentProfile`.
- T073-T078: Added cross-cutting response-shape, validation-contract, tenant-isolation, authorization, blocked-operation, and happy-path regression tests for the full student profile/enrollment slice.
- T079: Reviewed route inventory. Exactly five `/api/v1/student-profiles` routes are registered: list, create, detail, status update, and transfer. No blocked adjacent operations are registered.
- T080: PHP syntax check passed across `app`, `database`, `tests`, `routes`, and `bootstrap`.
- T081: Laravel Pint style check passed after mechanical formatting.
- T082: `php artisan test` was attempted. It cannot complete in this runtime because PHP has PDO installed but no MySQL or SQLite PDO driver; database-backed tests fail before executing application assertions.
- T083: Redocly validation passed for `aggregate@v1` and `schoolmaster-platform@v1`.
- T084: Final implementation notes record operation IDs, test commands, tenant rules, guardian association behavior, lifecycle behavior, transfer behavior, history preservation behavior, and blocked follow-up contract gaps.

## Final Operation Inventory

- `listStudentProfiles`: `GET /api/v1/student-profiles`
- `createStudentProfile`: `POST /api/v1/student-profiles`
- `getStudentProfile`: `GET /api/v1/student-profiles/{studentProfileId}`
- `updateStudentProfileStatus`: `PATCH /api/v1/student-profiles/{studentProfileId}/status`
- `transferStudentProfile`: `POST /api/v1/student-profiles/{studentProfileId}/transfer`

## Tenant and Authorization Rules

Every operation is behind `schoolmaster.auth` and `schoolmaster.school_context`, resolves an active `school_id` context before profile lookup or persistence, and requires school-scoped student administration permissions. Platform users, teachers, students, and guardians do not receive implicit student profile administration access.

## Guardian Association Behavior

Profile creation validates all supplied guardians as active and same-school before any profile is created. Guardian association creation, profile persistence, and initial enrollment history creation run in a single transaction.

## Lifecycle and Transfer Behavior

Status updates support only non-transfer lifecycle transitions and write append-only enrollment history atomically with profile status changes. Transfer is isolated to `transferStudentProfile`, marks only the source profile as transferred, records source-school transfer metadata and enrollment history, and does not copy grades, attendance, learning sets, private content, guardian links, report runs, or report outputs to another school.

## Verification Commands

- `npx @redocly/cli lint aggregate@v1 schoolmaster-platform@v1`: passed.
- `find app database tests routes bootstrap -name '*.php' -exec php -l {} +`: passed.
- `./vendor/bin/pint --test`: passed.
- `php artisan route:list --path=api/v1/student-profiles`: passed with five approved routes.
- `php artisan test`: blocked by missing PDO database driver (`could not find driver` for MySQL; SQLite driver is also unavailable).
