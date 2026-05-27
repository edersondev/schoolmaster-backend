<?php

use App\Http\Controllers\Api\V1\AcademicPeriodController;
use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\AccountInvitationController;
use App\Http\Controllers\Api\V1\AccountRecoveryController;
use App\Http\Controllers\Api\V1\AdministrationLifecycleController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BulkAdministrationLifecycleController;
use App\Http\Controllers\Api\V1\GradeController;
use App\Http\Controllers\Api\V1\GuardianController;
use App\Http\Controllers\Api\V1\LearningSetController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\QuestionnaireController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SchoolController;
use App\Http\Controllers\Api\V1\SchoolLifecycleController;
use App\Http\Controllers\Api\V1\StudentAttendanceController;
use App\Http\Controllers\Api\V1\StudentGradeController;
use App\Http\Controllers\Api\V1\StudentLearningSetController;
use App\Http\Controllers\Api\V1\StudentProfileController;
use App\Http\Controllers\Api\V1\StudentTeacherContentController;
use App\Http\Controllers\Api\V1\TeacherContentController;
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

        Route::middleware('schoolmaster.school_context')->group(function (): void {
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

            Route::get('/questionnaires', [QuestionnaireController::class, 'index'])->name('api.v1.questionnaires.index');
            Route::post('/questionnaires', [QuestionnaireController::class, 'store'])->name('api.v1.questionnaires.store');

            Route::get('/learning-sets', [LearningSetController::class, 'index'])->name('api.v1.learning-sets.index');
            Route::post('/learning-sets', [LearningSetController::class, 'store'])->name('api.v1.learning-sets.store');

            Route::get('/grades', [GradeController::class, 'index'])->name('api.v1.grades.index');
            Route::post('/grades', [GradeController::class, 'store'])->name('api.v1.grades.store');

            Route::get('/attendance', [AttendanceController::class, 'index'])->name('api.v1.attendance.index');
            Route::post('/attendance', [AttendanceController::class, 'store'])->name('api.v1.attendance.store');

            Route::prefix('student')->name('api.v1.student.')->group(function (): void {
                Route::get('/learning-sets', [StudentLearningSetController::class, 'index'])->name('learning-sets.index');
                Route::get('/grades', [StudentGradeController::class, 'index'])->name('grades.index');
                Route::get('/attendance', [StudentAttendanceController::class, 'index'])->name('attendance.index');
                Route::get('/teacher-content/{contentItemId}/download', [StudentTeacherContentController::class, 'download'])->name('teacher-content.download');
            });

            Route::prefix('reports')->name('api.v1.reports.')->group(function (): void {
                Route::get('/', [ReportController::class, 'index'])->name('index');
                Route::post('/', [ReportController::class, 'store'])->name('store');
                Route::get('/{reportRunId}/download', [ReportController::class, 'download'])->name('download');
            });
        });
    });
});
