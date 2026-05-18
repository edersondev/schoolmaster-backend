# Backend Teacher Workflow Foundation

Feature ID: `004-backend-teacher-workflows`

## Approved Operation IDs

- `listTeacherContent` - `GET /api/v1/teacher-content`
- `createTeacherContent` - `POST /api/v1/teacher-content`
- `listQuestionnaires` - `GET /api/v1/questionnaires`
- `createQuestionnaire` - `POST /api/v1/questionnaires`
- `listLearningSets` - `GET /api/v1/learning-sets`
- `createLearningSet` - `POST /api/v1/learning-sets`
- `listGrades` - `GET /api/v1/grades`
- `createGrade` - `POST /api/v1/grades`
- `listAttendance` - `GET /api/v1/attendance`
- `createAttendance` - `POST /api/v1/attendance`

## Contract Validation

- `npx @redocly/cli lint aggregate@v1 schoolmaster-platform@v1`
- Result before implementation: PASS

## Initial Route Inventory

No teacher workflow routes existed before this slice. Existing `/api/v1` routes were limited to auth, schools, permissions, roles, users, academic years, academic periods, guardians, and health.

## Final Route Inventory

Implemented teacher workflow routes:

- `GET /api/v1/teacher-content`
- `POST /api/v1/teacher-content`
- `GET /api/v1/questionnaires`
- `POST /api/v1/questionnaires`
- `GET /api/v1/learning-sets`
- `POST /api/v1/learning-sets`
- `GET /api/v1/grades`
- `POST /api/v1/grades`
- `GET /api/v1/attendance`
- `POST /api/v1/attendance`

All ten routes are under `schoolmaster.auth` and `schoolmaster.school_context`. No public folder CRUD, detail, update, delete, download, correction, report, classroom, course, section, roster, or student self-service teacher workflow routes were added.

## Blocked Contract Gaps

The backend must not expose public folder CRUD, downloads, detail, update, deactivate, delete, restore, bulk import, correction, student self-service, reporting, classroom, course, section, group, roster, or teacher-assignment workflows until `/specs` and OpenAPI document them.

## Persistence Inventory

Before this slice, the backend had school, user, role, permission, token, audit, academic year, academic period, student profile, and guardian tables. Teacher content, teacher content folders, questionnaires, questionnaire questions, learning sets, learning set entries, learning set assignments, grade records, and attendance records were missing and are added by this slice.

## Storage Behavior

Teacher content uploads use the private `teacher_content` filesystem disk rooted at `storage/app/private/teacher-content`. Stored paths are tenant-scoped by school UUID and content UUID. No public URL or download route is exposed by this slice.

## Scan-Status Behavior

Teacher content uploads initialize with `scan_status = pending`. Content can be marked `clean` or `failed` only from `pending` through `TeacherContentScanService`. Learning-set content entries require active same-school content with `scan_status = clean`.

## Verification Results

- PHP syntax: PASS (`find app database tests -name '*.php' -print0 | xargs -0 -n1 php -l`)
- Style: PASS (`./vendor/bin/pint --test`)
- OpenAPI: PASS (`npx @redocly/cli lint aggregate@v1 schoolmaster-platform@v1`)
- Focused non-DB unit tests: PASS (`php artisan test tests/Unit/Services/TeacherContentUploadValidationTest.php tests/Unit/Services/QuestionnaireValidationTest.php`)
- Full Docker PHPUnit suite: PASS (`docker compose exec -T app php artisan test`) - 71 passed, 368 assertions.
- Host PHPUnit suite: BLOCKED in this runtime. `php artisan test` fails before DB-backed tests run because PHP has `PDO` only and no `pdo_mysql` or `pdo_sqlite` driver loaded. The configured test database is MySQL (`DB_CONNECTION=mysql` in `phpunit.xml`).
