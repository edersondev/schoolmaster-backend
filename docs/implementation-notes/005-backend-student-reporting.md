# 005 Backend Student Reporting Implementation Notes

## Approved Operation Boundary

This backend slice is limited to the published OpenAPI operation IDs:

| Operation ID | Method | Path |
| --- | --- | --- |
| `listStudentLearningSets` | GET | `/api/v1/student/learning-sets` |
| `downloadStudentTeacherContent` | GET | `/api/v1/student/teacher-content/{contentItemId}/download` |
| `listStudentGrades` | GET | `/api/v1/student/grades` |
| `listStudentAttendance` | GET | `/api/v1/student/attendance` |
| `listReports` | GET | `/api/v1/reports` |
| `requestReport` | POST | `/api/v1/reports` |
| `downloadReport` | GET | `/api/v1/reports/{reportRunId}/download` |

## Contract Validation

- `npx @redocly/cli lint aggregate@v1 schoolmaster-platform@v1`
- Result before implementation: PASS. `api/openapi.yaml` and `specs/001-schoolmaster-platform/contracts/openapi.yaml` validated successfully.

## Current Route Inventory Before Implementation

Existing `/api/v1` product routes before this slice:

- Authentication: `POST /auth/login`, `GET /auth/me`, `POST /auth/logout`
- Platform school management: `GET /schools`, `POST /schools`, `GET /schools/{schoolId}`, `PATCH /schools/{schoolId}`
- School administration: `GET /permissions`, `GET /roles`, `POST /roles`, `GET /users`, `POST /users`, `GET /academic-years`, `POST /academic-years`, `GET /academic-periods`, `POST /academic-periods`, `GET /guardians`, `POST /guardians`
- Teacher workflows: `GET /teacher-content`, `POST /teacher-content`, `GET /questionnaires`, `POST /questionnaires`, `GET /learning-sets`, `POST /learning-sets`, `GET /grades`, `POST /grades`, `GET /attendance`, `POST /attendance`

No undocumented student, report, report retry, report deletion, guardian self-service, classroom, course, section, roster, or teacher correction routes existed before implementation.

## Blocked Contract Gaps

The following behavior remains blocked until `/specs` and OpenAPI are expanded:

- Frontend student/reporting implementation
- Custom report designer or custom report definitions
- Platform-wide reports or support-user report overrides
- Report deletion, restore, retry, cancellation, or manual status mutation endpoints
- Automatic expired-output regeneration during report download
- Student profile creation, update, transfer, or enrollment management
- Guardian self-service or guardian student views
- Classroom, course, section, group, roster, teacher assignment, or teacher correction workflows

## Persistence Inventory

- `StudentProfile`: existing `student_profiles` table includes UUID, `school_id`, `user_id`, `status`, and current academic year reference.
- `LearningSet`: existing `learning_sets` table includes UUID, `school_id`, owner, academic period, title, `published_at`, status, soft deletes, and tenant/status indexes.
- `LearningSetEntry`: existing `learning_set_entries` table includes UUID, `school_id`, learning set, entry type/reference, sequence, and tenant/type index.
- `LearningSetAssignment`: existing `learning_set_assignments` table includes UUID, `school_id`, learning set, student profile, status, `assigned_at`, unique assignment, and tenant/status index.
- `TeacherContentItem`: existing `teacher_content_items` table includes UUID, `school_id`, owner, private `storage_path`, scan status, operational status, soft deletes, and tenant/status/scan indexes.
- `GradeRecord`: existing `grade_records` table includes UUID, `school_id`, student profile, academic period, recorder, grade fields, status, `recorded_at`, soft deletes, and tenant/student-period indexes.
- `AttendanceRecord`: existing `attendance_records` table includes UUID, `school_id`, student profile, academic period, recorder, attendance date/status, status, soft deletes, and tenant/student-period indexes.
- `ReportRun`: added by `2026_05_20_000001_create_report_run_output_tables.php` with UUID, `school_id`, requester, report type, filters, output formats, status, generated timestamp, output expiry, availability flag, soft deletes, and tenant indexes.
- `ReportOutput`: added by `2026_05_20_000001_create_report_run_output_tables.php` with UUID, `school_id`, report run, format, private storage path, generated/expiry timestamps, status, soft deletes, and tenant/status indexes.

## Final Operation Inventory

Implemented approved routes:

- `listStudentLearningSets`: `GET /api/v1/student/learning-sets`
- `downloadStudentTeacherContent`: `GET /api/v1/student/teacher-content/{contentItemId}/download`
- `listStudentGrades`: `GET /api/v1/student/grades`
- `listStudentAttendance`: `GET /api/v1/student/attendance`
- `listReports`: `GET /api/v1/reports`
- `requestReport`: `POST /api/v1/reports`
- `downloadReport`: `GET /api/v1/reports/{reportRunId}/download`

Route review after implementation confirms no report retry, report deletion, custom report, guardian self-service, student profile management, classroom/course/section/roster, or teacher correction routes were added.

## Behavior Notes

- Student self-view resolves the authenticated user's active same-school `StudentProfile` and constrains learning sets, grades, attendance, and teacher-content downloads to that profile.
- Student content download requires same-school content, active assigned learning set linkage, `status = active`, and `scan_status = clean`; private storage paths are not exposed in JSON responses.
- Report requests create `ReportRun` records with `requested` status and output availability metadata without waiting for generated files. `GenerateReportRunOutputs` is the async job boundary for creating private PDF/CSV outputs.
- Report output downloads require same-school generated report runs, requested documented format, private stored output, and unexpired output metadata.
- Expired report outputs return the documented `output_expired` envelope and do not regenerate during download. Regeneration remains a new `requestReport` call with the same filters.

## Verification Results

- PHP syntax: PASS. `php -l` completed for app, test, migration, factory, and seeder PHP files.
- Style: PASS. `./vendor/bin/pint --test` passed after formatting.
- Route review: PASS. `php artisan route:list --path=api/v1` shows the seven approved student/reporting operations and no blocked lifecycle routes.
- OpenAPI validation: PASS. `npx @redocly/cli lint aggregate@v1 schoolmaster-platform@v1` validated both contracts.
- PHPUnit: PASS in Docker. `docker exec schoolmaster-backend-app-1 php artisan test` completed with 95 passing tests and 432 assertions.
- Host PHPUnit note: direct host `php artisan test` is not usable in this environment because the host PHP runtime lacks the configured MySQL PDO driver.
