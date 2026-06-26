# SchoolMaster Backend

Laravel API backend for the SchoolMaster SaaS platform.

## Source Of Truth

The SchoolMaster specs repository is authoritative for product behavior, API
contracts, business rules, architecture decisions, and implementation
sequencing.

Specs repository:

- https://github.com/edersondev/schoolmaster-specs

In local development, this backend repo may expose that repository through a
`specs/` symlink. Before changing backend behavior, read:

1. `specs/AGENTS.md`
2. Relevant files under `specs/specs`
3. `specs/api/openapi.yaml`
4. Relevant files under `specs/docs`
5. Relevant files under `specs/decisions`

If backend code conflicts with the specs repository, update the specs and
OpenAPI contract first. Backend implementation follows documented contracts
only.

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan test
```

SchoolMaster uses MySQL as the primary transactional datastore. The default
`.env.example` values target a local `schoolmaster` database.

## API Boundary

Product APIs are RESTful and versioned under `/api/v1`. The approved backend
foundation, school administration, and report lifecycle slices are limited to
these OpenAPI operation IDs:

- `login`
- `getCurrentUser`
- `logout`
- `listSchools`
- `createSchool`
- `getSchool`
- `updateSchool`
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
- `createGuardianUserLink`
- `deactivateGuardianUserLink`
- `listGuardianStudents`
- `getGuardianStudent`
- `getGuardianStudentAcademics`
- `getGuardianStudentContacts`
- `listReports`
- `requestReport`
- `downloadReport`
- `retryReport`
- `cancelReport`
- `deleteReport`
- `restoreReport`
- `getReportCatalog`
- `listReportDefinitions`
- `createReportDefinition`
- `getReportDefinition`
- `updateReportDefinition`
- `activateReportDefinition`
- `deactivateReportDefinition`
- `deleteReportDefinition`
- `restoreReportDefinition`
- `listPlatformSchoolSummaries`
- `getPlatformReportingOverview`
- `requestSupportAccess`
- `getSupportAccessDecision`
- `approveSupportAccess`
- `revokeSupportAccess`
- `createSchoolSupportOptIn`
- `revokeSchoolSupportOptIn`
- `getSupportSchoolDiagnostics`
- `listSupportAuditEvents`
- `createQuestionnaire`
- `getQuestionnaire`
- `updateQuestionnaire`
- `submitStudentQuestionnaireResponse`
- `getStudentQuestionnaireResponse`
- `listQuestionnaireResponses`
- `getQuestionnaireResponse`
- `gradeQuestionnaireResponse`
- `downloadQuestionnaireResponseFile`

## Report Lifecycle Expansion

The report lifecycle expansion implements school-scoped report run lifecycle
actions, custom report definitions, catalog-approved custom report requests,
per-format output availability, and catalog-approved XLSX output support.

Report data remains school-owned through `school_id`. Report runs and custom
definitions use UUID route identifiers, lifecycle actions are audited through
tenant-safe reason codes, report-run delete is soft delete only, and output
download responses never expose storage paths. Custom report definitions are
unique per school among non-deleted definitions; active definitions allow only
name and description updates.

Operational framework routes, such as Laravel health checks, are not product
feature routes. Product Blade views are not part of this backend.

## Platform Support Access

The platform support access slice adds explicit platform-scoped visibility for
minimized school operational summaries, cross-school reporting overview,
read-only support diagnostics, target-school support opt-ins, internal platform
approvals, and minimized support audit review.

Support diagnostics require both a same-school support opt-in and an internal
platform approval, each within the 24-hour window. Platform support routes do
not expose generated report downloads, raw report outputs, private file paths,
emergency access, impersonation, unrestricted search, or support writes.
Protected aggregate counts below 5 are suppressed, and platform support audit
metadata is redacted before storage.

## Advanced Assessment Content Types

The advanced assessment slice extends questionnaires with `long_text` and
`file_response` questions while preserving existing v1 question behavior and
historical-meaning locks. Students may submit one assigned same-school response
attempt under `/api/v1/student/questionnaire-responses`; submissions are
tenant-scoped, due-date gated, and stored atomically.

Answer files are stored privately, limited to one safe PDF, image, text, or
office file per file-response question, capped at 25 MB, and scan-gated before
review download. Teachers and school administrators can review same-school
responses, download only clean answer files, and record manual 0-100 grading.
Failed scan file answers may only receive zero or exempt outcomes.

Student views expose only own response status, grading status, score summary,
teacher feedback summary, and safe file availability metadata. Reports expose
only advanced assessment aggregate fields: response count, completion status,
grading status, and score summary. Raw answers, uploaded files, file links,
private metadata, storage paths, answer keys, and private grading notes remain
excluded from reports, guardian views, platform support views, and audit
metadata.

## Contract Validation

Run OpenAPI validation before merging product-visible behavior:

```bash
npx @redocly/cli lint --config specs/redocly.yaml aggregate@v1 schoolmaster-platform@v1
```

## Backend Validation

```bash
php artisan route:list
php artisan test
```

`php artisan route:list` should show product routes only under `/api/v1` and no
undocumented product route outside `routes/api.php`.

## Docker Test Runtime

Use Docker Compose when the host PHP runtime does not include a PDO database
driver or when tests should run against MySQL:

```bash
cp docker/.env.example docker/.env
docker compose -f docker/docker-compose.yml -f docker/docker-compose.test.yml up -d
docker compose -f docker/docker-compose.yml -f docker/docker-compose.test.yml exec app php artisan test
```

The Compose stack provides PHP 8.3 with `pdo_mysql`, a main MySQL container
published on `3306`, and a dedicated test MySQL container published on `3308`.
PHPUnit uses `.env.testing`, which points to the `dbmysql_test` service on the
container network.
