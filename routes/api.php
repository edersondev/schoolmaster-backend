<?php

use App\Http\Controllers\Api\V1\AcademicPeriodController;
use App\Http\Controllers\Api\V1\AcademicRecordController;
use App\Http\Controllers\Api\V1\AcademicRecordImportController;
use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\AccountInvitationController;
use App\Http\Controllers\Api\V1\AccountRecoveryController;
use App\Http\Controllers\Api\V1\AdministrationLifecycleController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BulkAdministrationLifecycleController;
use App\Http\Controllers\Api\V1\ClassSectionController;
use App\Http\Controllers\Api\V1\GradeController;
use App\Http\Controllers\Api\V1\Guardian\GuardianSelfServiceController;
use App\Http\Controllers\Api\V1\GuardianController;
use App\Http\Controllers\Api\V1\LearningSetController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\Platform\PlatformSupportController;
use App\Http\Controllers\Api\V1\QuestionnaireController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReportDefinitionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\RosterMembershipController;
use App\Http\Controllers\Api\V1\SchoolController;
use App\Http\Controllers\Api\V1\SchoolLifecycleController;
use App\Http\Controllers\Api\V1\StudentAttendanceController;
use App\Http\Controllers\Api\V1\StudentGradeController;
use App\Http\Controllers\Api\V1\StudentLearningSetController;
use App\Http\Controllers\Api\V1\StudentProfileController;
use App\Http\Controllers\Api\V1\StudentTeacherContentController;
use App\Http\Controllers\Api\V1\TeacherAssignmentController;
use App\Http\Controllers\Api\V1\TeacherContentController;
use App\Http\Controllers\Api\V1\TeacherMaterialsController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => ['status' => 'ok'])->name('api.v1.health');

    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    Route::post('/auth/password-reset-requests', [PasswordResetController::class, 'request'])->name('api.v1.auth.password-reset-requests');
    Route::post('/auth/password-resets', [PasswordResetController::class, 'complete'])->name('api.v1.auth.password-resets');
    Route::post('/account-invitations/{invitationToken}/setup', [AccountInvitationController::class, 'complete'])->name('api.v1.account-invitations.setup');

    Route::middleware('schoolmaster.auth')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        Route::post('/account-invitations', [AccountInvitationController::class, 'store'])->name('api.v1.account-invitations.store');
        Route::post('/account-invitations/{invitationToken}/resend', [AccountInvitationController::class, 'resend'])->name('api.v1.account-invitations.resend');
        Route::get('/users/{userId}/account-lock', [AccountRecoveryController::class, 'show'])->whereUuid('userId')->name('api.v1.users.account-lock.show');
        Route::post('/users/{userId}/account-lock', [AccountRecoveryController::class, 'lock'])->whereUuid('userId')->name('api.v1.users.account-lock.store');
        Route::delete('/users/{userId}/account-lock', [AccountRecoveryController::class, 'unlock'])->whereUuid('userId')->name('api.v1.users.account-lock.destroy');
        Route::post('/users/{userId}/account-reactivation', [AccountRecoveryController::class, 'recover'])->whereUuid('userId')->name('api.v1.users.account-reactivation.store');

        Route::get('/schools', [SchoolController::class, 'index'])->name('api.v1.schools.index');
        Route::post('/schools', [SchoolController::class, 'store'])->name('api.v1.schools.store');
        Route::get('/schools/{schoolId}', [SchoolLifecycleController::class, 'show'])->whereUuid('schoolId')->name('api.v1.schools.show');
        Route::patch('/schools/{schoolId}', [SchoolLifecycleController::class, 'update'])->whereUuid('schoolId')->name('api.v1.schools.update');
        Route::post('/schools/{schoolId}/activate', [SchoolLifecycleController::class, 'activate'])->whereUuid('schoolId')->name('api.v1.schools.activate');
        Route::post('/schools/{schoolId}/deactivate', [SchoolLifecycleController::class, 'deactivate'])->whereUuid('schoolId')->name('api.v1.schools.deactivate');
        Route::delete('/schools/{schoolId}', [SchoolLifecycleController::class, 'delete'])->whereUuid('schoolId')->name('api.v1.schools.delete');
        Route::post('/schools/{schoolId}/restore', [SchoolLifecycleController::class, 'restore'])->whereUuid('schoolId')->name('api.v1.schools.restore');
        Route::post('/schools/{schoolId}/support-opt-ins', [PlatformSupportController::class, 'createSchoolSupportOptIn'])->whereUuid('schoolId')->name('api.v1.schools.support-opt-ins.store');
        Route::post('/schools/{schoolId}/support-opt-ins/{supportOptInId}/revoke', [PlatformSupportController::class, 'revokeSchoolSupportOptIn'])->whereUuid('schoolId')->whereUuid('supportOptInId')->name('api.v1.schools.support-opt-ins.revoke');

        Route::prefix('platform')->name('api.v1.platform.')->group(function (): void {
            Route::get('/schools', [PlatformSupportController::class, 'schools'])->name('schools.index');
            Route::get('/reporting/overview', [PlatformSupportController::class, 'reportingOverview'])->name('reporting.overview');
            Route::post('/support-access', [PlatformSupportController::class, 'requestSupportAccess'])->name('support-access.store');
            Route::get('/support-access/{supportAccessId}', [PlatformSupportController::class, 'showSupportAccess'])->whereUuid('supportAccessId')->name('support-access.show');
            Route::post('/support-access/{supportAccessId}/approve', [PlatformSupportController::class, 'approveSupportAccess'])->whereUuid('supportAccessId')->name('support-access.approve');
            Route::post('/support-access/{supportAccessId}/revoke', [PlatformSupportController::class, 'revokeSupportAccess'])->whereUuid('supportAccessId')->name('support-access.revoke');
            Route::get('/support/schools/{schoolId}/diagnostics', [PlatformSupportController::class, 'diagnostics'])->whereUuid('schoolId')->name('support.schools.diagnostics');
            Route::get('/support-audit-events', [PlatformSupportController::class, 'auditEvents'])->name('support-audit-events.index');
        });

        Route::middleware('schoolmaster.school_context')->group(function (): void {
            Route::prefix('teacher-workflow')->group(function (): void {
                // Phase 2 placeholder group for teacher workflow lifecycle expansion.
            });

            Route::get('/permissions', [PermissionController::class, 'index'])->name('api.v1.permissions.index');

            Route::get('/roles', [RoleController::class, 'index'])->name('api.v1.roles.index');
            Route::post('/roles', [RoleController::class, 'store'])->name('api.v1.roles.store');
            Route::post('/roles/bulk-lifecycle', [BulkAdministrationLifecycleController::class, 'roles'])->name('api.v1.roles.bulk-lifecycle');
            Route::get('/roles/{roleId}', [AdministrationLifecycleController::class, 'showRole'])->whereUuid('roleId')->name('api.v1.roles.show');
            Route::patch('/roles/{roleId}', [AdministrationLifecycleController::class, 'updateRole'])->whereUuid('roleId')->name('api.v1.roles.update');
            Route::post('/roles/{roleId}/activate', [AdministrationLifecycleController::class, 'activateRole'])->whereUuid('roleId')->name('api.v1.roles.activate');
            Route::post('/roles/{roleId}/deactivate', [AdministrationLifecycleController::class, 'deactivateRole'])->whereUuid('roleId')->name('api.v1.roles.deactivate');
            Route::delete('/roles/{roleId}', [AdministrationLifecycleController::class, 'deleteRole'])->whereUuid('roleId')->name('api.v1.roles.delete');
            Route::post('/roles/{roleId}/restore', [AdministrationLifecycleController::class, 'restoreRole'])->whereUuid('roleId')->name('api.v1.roles.restore');

            Route::get('/users', [UserController::class, 'index'])->name('api.v1.users.index');
            Route::post('/users', [UserController::class, 'store'])->name('api.v1.users.store');
            Route::post('/users/bulk-lifecycle', [BulkAdministrationLifecycleController::class, 'users'])->name('api.v1.users.bulk-lifecycle');
            Route::get('/users/{userId}', [AdministrationLifecycleController::class, 'showUser'])->whereUuid('userId')->name('api.v1.users.show');
            Route::patch('/users/{userId}', [AdministrationLifecycleController::class, 'updateUser'])->whereUuid('userId')->name('api.v1.users.update');
            Route::post('/users/{userId}/activate', [AdministrationLifecycleController::class, 'activateUser'])->whereUuid('userId')->name('api.v1.users.activate');
            Route::post('/users/{userId}/deactivate', [AdministrationLifecycleController::class, 'deactivateUser'])->whereUuid('userId')->name('api.v1.users.deactivate');
            Route::delete('/users/{userId}', [AdministrationLifecycleController::class, 'deleteUser'])->whereUuid('userId')->name('api.v1.users.delete');
            Route::post('/users/{userId}/restore', [AdministrationLifecycleController::class, 'restoreUser'])->whereUuid('userId')->name('api.v1.users.restore');

            Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('api.v1.academic-years.index');
            Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('api.v1.academic-years.store');
            Route::post('/academic-years/bulk-lifecycle', [BulkAdministrationLifecycleController::class, 'academicYears'])->name('api.v1.academic-years.bulk-lifecycle');
            Route::get('/academic-years/{academicYearId}', [AdministrationLifecycleController::class, 'showAcademicYear'])->whereUuid('academicYearId')->name('api.v1.academic-years.show');
            Route::patch('/academic-years/{academicYearId}', [AdministrationLifecycleController::class, 'updateAcademicYear'])->whereUuid('academicYearId')->name('api.v1.academic-years.update');
            Route::post('/academic-years/{academicYearId}/activate', [AdministrationLifecycleController::class, 'activateAcademicYear'])->whereUuid('academicYearId')->name('api.v1.academic-years.activate');
            Route::post('/academic-years/{academicYearId}/deactivate', [AdministrationLifecycleController::class, 'deactivateAcademicYear'])->whereUuid('academicYearId')->name('api.v1.academic-years.deactivate');
            Route::delete('/academic-years/{academicYearId}', [AdministrationLifecycleController::class, 'deleteAcademicYear'])->whereUuid('academicYearId')->name('api.v1.academic-years.delete');
            Route::post('/academic-years/{academicYearId}/restore', [AdministrationLifecycleController::class, 'restoreAcademicYear'])->whereUuid('academicYearId')->name('api.v1.academic-years.restore');

            Route::get('/academic-periods', [AcademicPeriodController::class, 'index'])->name('api.v1.academic-periods.index');
            Route::post('/academic-periods', [AcademicPeriodController::class, 'store'])->name('api.v1.academic-periods.store');
            Route::post('/academic-periods/bulk-lifecycle', [BulkAdministrationLifecycleController::class, 'academicPeriods'])->name('api.v1.academic-periods.bulk-lifecycle');
            Route::get('/academic-periods/{academicPeriodId}', [AdministrationLifecycleController::class, 'showAcademicPeriod'])->whereUuid('academicPeriodId')->name('api.v1.academic-periods.show');
            Route::patch('/academic-periods/{academicPeriodId}', [AdministrationLifecycleController::class, 'updateAcademicPeriod'])->whereUuid('academicPeriodId')->name('api.v1.academic-periods.update');
            Route::post('/academic-periods/{academicPeriodId}/activate', [AdministrationLifecycleController::class, 'activateAcademicPeriod'])->whereUuid('academicPeriodId')->name('api.v1.academic-periods.activate');
            Route::post('/academic-periods/{academicPeriodId}/deactivate', [AdministrationLifecycleController::class, 'deactivateAcademicPeriod'])->whereUuid('academicPeriodId')->name('api.v1.academic-periods.deactivate');
            Route::delete('/academic-periods/{academicPeriodId}', [AdministrationLifecycleController::class, 'deleteAcademicPeriod'])->whereUuid('academicPeriodId')->name('api.v1.academic-periods.delete');
            Route::post('/academic-periods/{academicPeriodId}/restore', [AdministrationLifecycleController::class, 'restoreAcademicPeriod'])->whereUuid('academicPeriodId')->name('api.v1.academic-periods.restore');

            Route::get('/guardians', [GuardianController::class, 'index'])->name('api.v1.guardians.index');
            Route::post('/guardians', [GuardianController::class, 'store'])->name('api.v1.guardians.store');
            Route::post('/guardians/bulk-lifecycle', [BulkAdministrationLifecycleController::class, 'guardians'])->name('api.v1.guardians.bulk-lifecycle');
            Route::post('/guardians/{guardianId}/user-links', [GuardianController::class, 'createUserLink'])->whereUuid('guardianId')->name('api.v1.guardians.user-links.store');
            Route::post('/guardians/{guardianId}/user-links/{guardianUserLinkId}/deactivate', [GuardianController::class, 'deactivateUserLink'])->whereUuid('guardianId')->whereUuid('guardianUserLinkId')->name('api.v1.guardians.user-links.deactivate');
            Route::get('/guardians/{guardianId}', [AdministrationLifecycleController::class, 'showGuardian'])->whereUuid('guardianId')->name('api.v1.guardians.show');
            Route::patch('/guardians/{guardianId}', [AdministrationLifecycleController::class, 'updateGuardian'])->whereUuid('guardianId')->name('api.v1.guardians.update');
            Route::post('/guardians/{guardianId}/activate', [AdministrationLifecycleController::class, 'activateGuardian'])->whereUuid('guardianId')->name('api.v1.guardians.activate');
            Route::post('/guardians/{guardianId}/deactivate', [AdministrationLifecycleController::class, 'deactivateGuardian'])->whereUuid('guardianId')->name('api.v1.guardians.deactivate');
            Route::delete('/guardians/{guardianId}', [AdministrationLifecycleController::class, 'deleteGuardian'])->whereUuid('guardianId')->name('api.v1.guardians.delete');
            Route::post('/guardians/{guardianId}/restore', [AdministrationLifecycleController::class, 'restoreGuardian'])->whereUuid('guardianId')->name('api.v1.guardians.restore');

            Route::prefix('student-profiles')->name('api.v1.student-profiles.')->group(function (): void {
                Route::get('/', [StudentProfileController::class, 'index'])->name('index');
                Route::post('/', [StudentProfileController::class, 'store'])->name('store');
                Route::patch('/{studentProfileId}/status', [StudentProfileController::class, 'updateStatus'])->whereUuid('studentProfileId')->name('status.update');
                Route::post('/{studentProfileId}/transfer', [StudentProfileController::class, 'transfer'])->whereUuid('studentProfileId')->name('transfer');
                Route::get('/{studentProfileId}', [StudentProfileController::class, 'show'])->whereUuid('studentProfileId')->name('show');
            });

            Route::get('/teacher-content', [TeacherContentController::class, 'index'])->name('api.v1.teacher-content.index');
            Route::post('/teacher-content', [TeacherContentController::class, 'store'])->name('api.v1.teacher-content.store');
            Route::get('/teacher-content/{contentItemId}', [TeacherMaterialsController::class, 'showContent'])->whereUuid('contentItemId')->name('api.v1.teacher-content.show');
            Route::patch('/teacher-content/{contentItemId}', [TeacherMaterialsController::class, 'updateContent'])->whereUuid('contentItemId')->name('api.v1.teacher-content.update');
            Route::patch('/teacher-content/{contentItemId}/status', [TeacherMaterialsController::class, 'updateContentStatus'])->whereUuid('contentItemId')->name('api.v1.teacher-content.status.update');
            Route::delete('/teacher-content/{contentItemId}', [TeacherMaterialsController::class, 'deleteContent'])->whereUuid('contentItemId')->name('api.v1.teacher-content.delete');
            Route::post('/teacher-content/{contentItemId}/restore', [TeacherMaterialsController::class, 'restoreContent'])->whereUuid('contentItemId')->name('api.v1.teacher-content.restore');
            Route::get('/teacher-content/{contentItemId}/download', [TeacherMaterialsController::class, 'downloadContent'])->whereUuid('contentItemId')->name('api.v1.teacher-content.download');

            Route::get('/questionnaires', [QuestionnaireController::class, 'index'])->name('api.v1.questionnaires.index');
            Route::post('/questionnaires', [QuestionnaireController::class, 'store'])->name('api.v1.questionnaires.store');
            Route::get('/questionnaires/{questionnaireId}', [TeacherMaterialsController::class, 'showQuestionnaire'])->whereUuid('questionnaireId')->name('api.v1.questionnaires.show');
            Route::patch('/questionnaires/{questionnaireId}', [TeacherMaterialsController::class, 'updateQuestionnaire'])->whereUuid('questionnaireId')->name('api.v1.questionnaires.update');
            Route::patch('/questionnaires/{questionnaireId}/status', [TeacherMaterialsController::class, 'updateQuestionnaireStatus'])->whereUuid('questionnaireId')->name('api.v1.questionnaires.status.update');
            Route::delete('/questionnaires/{questionnaireId}', [TeacherMaterialsController::class, 'deleteQuestionnaire'])->whereUuid('questionnaireId')->name('api.v1.questionnaires.delete');
            Route::post('/questionnaires/{questionnaireId}/restore', [TeacherMaterialsController::class, 'restoreQuestionnaire'])->whereUuid('questionnaireId')->name('api.v1.questionnaires.restore');

            Route::get('/learning-sets', [LearningSetController::class, 'index'])->name('api.v1.learning-sets.index');
            Route::post('/learning-sets', [LearningSetController::class, 'store'])->name('api.v1.learning-sets.store');
            Route::get('/learning-sets/{learningSetId}', [LearningSetController::class, 'show'])->whereUuid('learningSetId')->name('api.v1.learning-sets.show');
            Route::patch('/learning-sets/{learningSetId}', [LearningSetController::class, 'update'])->whereUuid('learningSetId')->name('api.v1.learning-sets.update');
            Route::patch('/learning-sets/{learningSetId}/status', [LearningSetController::class, 'updateStatus'])->whereUuid('learningSetId')->name('api.v1.learning-sets.status.update');
            Route::delete('/learning-sets/{learningSetId}', [LearningSetController::class, 'delete'])->whereUuid('learningSetId')->name('api.v1.learning-sets.delete');
            Route::post('/learning-sets/{learningSetId}/restore', [LearningSetController::class, 'restore'])->whereUuid('learningSetId')->name('api.v1.learning-sets.restore');

            Route::prefix('class-sections')->name('api.v1.class-sections.')->group(function (): void {
                Route::get('/', [ClassSectionController::class, 'index'])->name('index');
                Route::post('/', [ClassSectionController::class, 'store'])->name('store');
                Route::get('/{classSectionId}', [ClassSectionController::class, 'show'])->whereUuid('classSectionId')->name('show');
                Route::patch('/{classSectionId}', [ClassSectionController::class, 'update'])->whereUuid('classSectionId')->name('update');
                Route::patch('/{classSectionId}/status', [ClassSectionController::class, 'updateStatus'])->whereUuid('classSectionId')->name('status.update');
                Route::get('/{classSectionId}/memberships', [RosterMembershipController::class, 'index'])->whereUuid('classSectionId')->name('memberships.index');
                Route::post('/{classSectionId}/memberships', [RosterMembershipController::class, 'store'])->whereUuid('classSectionId')->name('memberships.store');
                Route::patch('/{classSectionId}/memberships', [RosterMembershipController::class, 'update'])->whereUuid('classSectionId')->name('memberships.update');
            });

            Route::prefix('teacher-assignments')->name('api.v1.teacher-assignments.')->group(function (): void {
                Route::get('/', [TeacherAssignmentController::class, 'index'])->name('index');
                Route::post('/', [TeacherAssignmentController::class, 'store'])->name('store');
                Route::get('/{teacherAssignmentId}', [TeacherAssignmentController::class, 'show'])->whereUuid('teacherAssignmentId')->name('show');
                Route::patch('/{teacherAssignmentId}/status', [TeacherAssignmentController::class, 'updateStatus'])->whereUuid('teacherAssignmentId')->name('status.update');
            });

            Route::get('/grades', [GradeController::class, 'index'])->name('api.v1.grades.index');
            Route::post('/grades', [GradeController::class, 'store'])->name('api.v1.grades.store');
            Route::post('/grades/imports', [AcademicRecordImportController::class, 'importGrades'])->name('api.v1.grades.imports');
            Route::get('/grades/{gradeId}', [AcademicRecordController::class, 'showGrade'])->whereUuid('gradeId')->name('api.v1.grades.show');
            Route::patch('/grades/{gradeId}/correction', [AcademicRecordController::class, 'correctGrade'])->whereUuid('gradeId')->name('api.v1.grades.correction');
            Route::patch('/grades/{gradeId}/status', [AcademicRecordController::class, 'updateGradeStatus'])->whereUuid('gradeId')->name('api.v1.grades.status.update');
            Route::delete('/grades/{gradeId}', [AcademicRecordController::class, 'deleteGrade'])->whereUuid('gradeId')->name('api.v1.grades.delete');
            Route::post('/grades/{gradeId}/restore', [AcademicRecordController::class, 'restoreGrade'])->whereUuid('gradeId')->name('api.v1.grades.restore');

            Route::get('/attendance', [AttendanceController::class, 'index'])->name('api.v1.attendance.index');
            Route::post('/attendance', [AttendanceController::class, 'store'])->name('api.v1.attendance.store');
            Route::post('/attendance/imports', [AcademicRecordImportController::class, 'importAttendance'])->name('api.v1.attendance.imports');
            Route::get('/attendance/{attendanceId}', [AcademicRecordController::class, 'showAttendance'])->whereUuid('attendanceId')->name('api.v1.attendance.show');
            Route::patch('/attendance/{attendanceId}/correction', [AcademicRecordController::class, 'correctAttendance'])->whereUuid('attendanceId')->name('api.v1.attendance.correction');
            Route::patch('/attendance/{attendanceId}/status', [AcademicRecordController::class, 'updateAttendanceStatus'])->whereUuid('attendanceId')->name('api.v1.attendance.status.update');
            Route::delete('/attendance/{attendanceId}', [AcademicRecordController::class, 'deleteAttendance'])->whereUuid('attendanceId')->name('api.v1.attendance.delete');
            Route::post('/attendance/{attendanceId}/restore', [AcademicRecordController::class, 'restoreAttendance'])->whereUuid('attendanceId')->name('api.v1.attendance.restore');

            Route::prefix('student')->name('api.v1.student.')->group(function (): void {
                Route::get('/learning-sets', [StudentLearningSetController::class, 'index'])->name('learning-sets.index');
                Route::get('/grades', [StudentGradeController::class, 'index'])->name('grades.index');
                Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
                Route::get('/teacher-content/{contentItemId}/download', [StudentTeacherContentController::class, 'download'])->name('teacher-content.download');
            });

            Route::prefix('guardian')->name('api.v1.guardian.')->group(function (): void {
                Route::get('/students', [GuardianSelfServiceController::class, 'index'])->name('students.index');
                Route::get('/students/{studentProfileId}', [GuardianSelfServiceController::class, 'show'])->whereUuid('studentProfileId')->name('students.show');
                Route::get('/students/{studentProfileId}/academics', [GuardianSelfServiceController::class, 'academics'])->whereUuid('studentProfileId')->name('students.academics');
                Route::get('/students/{studentProfileId}/contacts', [GuardianSelfServiceController::class, 'contacts'])->whereUuid('studentProfileId')->name('students.contacts');
            });

            Route::prefix('reports')->name('api.v1.reports.')->group(function (): void {
                Route::get('/', [ReportController::class, 'index'])->name('index');
                Route::post('/', [ReportController::class, 'store'])->name('store');
                Route::post('/{reportRunId}/retry', [ReportController::class, 'retry'])->whereUuid('reportRunId')->name('retry');
                Route::post('/{reportRunId}/cancel', [ReportController::class, 'cancel'])->whereUuid('reportRunId')->name('cancel');
                Route::get('/{reportRunId}/download', [ReportController::class, 'download'])->name('download');
                Route::delete('/{reportRunId}', [ReportController::class, 'delete'])->whereUuid('reportRunId')->name('delete');
                Route::post('/{reportRunId}/restore', [ReportController::class, 'restore'])->whereUuid('reportRunId')->name('restore');
            });

            Route::get('/report-catalog', [ReportDefinitionController::class, 'catalog'])->name('api.v1.report-catalog.index');

            Route::prefix('report-definitions')->name('api.v1.report-definitions.')->group(function (): void {
                Route::get('/', [ReportDefinitionController::class, 'index'])->name('index');
                Route::post('/', [ReportDefinitionController::class, 'store'])->name('store');
                Route::get('/{reportDefinitionId}', [ReportDefinitionController::class, 'show'])->whereUuid('reportDefinitionId')->name('show');
                Route::patch('/{reportDefinitionId}', [ReportDefinitionController::class, 'update'])->whereUuid('reportDefinitionId')->name('update');
                Route::post('/{reportDefinitionId}/activate', [ReportDefinitionController::class, 'activate'])->whereUuid('reportDefinitionId')->name('activate');
                Route::post('/{reportDefinitionId}/deactivate', [ReportDefinitionController::class, 'deactivate'])->whereUuid('reportDefinitionId')->name('deactivate');
                Route::delete('/{reportDefinitionId}', [ReportDefinitionController::class, 'delete'])->whereUuid('reportDefinitionId')->name('delete');
                Route::post('/{reportDefinitionId}/restore', [ReportDefinitionController::class, 'restore'])->whereUuid('reportDefinitionId')->name('restore');
            });
        });
    });
});
