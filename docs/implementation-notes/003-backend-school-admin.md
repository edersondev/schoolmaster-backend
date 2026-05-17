# 003 Backend School Admin Implementation Notes

## Scope

Implemented operations are limited to:

- `listUsers`
- `createUser`
- `listRoles`
- `createRole`
- `listPermissions`
- `listAcademicYears`
- `createAcademicYear`
- `listAcademicPeriods`
- `createAcademicPeriod`
- `listGuardians`
- `createGuardian`

Blocked operations remain: detail, update, activate, deactivate, delete,
restore, invitation, password management, student-profile creation, teacher
workflow, student self-service, and reporting behavior.

## Contract Validation

- `npx @redocly/cli lint specs/api/openapi.yaml`: valid with one pre-existing
  `info.license` warning.
- `npx @redocly/cli lint specs/specs/001-schoolmaster-platform/contracts/openapi.yaml`:
  valid with one pre-existing `info.license` warning.

## Route Inventory

`php artisan route:list --path=api/v1` shows only the approved foundation and
school-admin product routes plus the operational health route:

- auth: `login`, `getCurrentUser`, `logout`
- schools: `listSchools`, `createSchool`, `getSchool`, `updateSchool`
- school admin: `listUsers`, `createUser`, `listRoles`, `createRole`,
  `listPermissions`, `listAcademicYears`, `createAcademicYear`,
  `listAcademicPeriods`, `createAcademicPeriod`, `listGuardians`,
  `createGuardian`

## Persistence Notes

School-admin persistence is implemented with status fields rather than public
delete behavior. No product delete or restore operation is exposed in this
slice.

New school-owned tables use `school_id` as the tenant column:

- `academic_years`
- `academic_periods`
- `student_profiles`
- `guardians`
- `guardian_student_profile`

## Test Results

- `find app database tests routes bootstrap -name '*.php' -print0 | xargs -0 -n1 php -l`:
  passed.
- `./vendor/bin/pint --test`: passed.
- `docker compose run --rm app php artisan test`: 39 passed, 206 assertions.

Host `php artisan test` is not usable in this environment because the local PHP
runtime has PDO but no database drivers. The Docker test runtime provides
`pdo_mysql` and is the successful verification path.
