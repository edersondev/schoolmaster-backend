<?php

use App\Http\Controllers\Api\V1\AcademicPeriodController;
use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GradeController;
use App\Http\Controllers\Api\V1\GuardianController;
use App\Http\Controllers\Api\V1\LearningSetController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\QuestionnaireController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SchoolController;
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
    Route::middleware('schoolmaster.auth')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        Route::get('/schools', [SchoolController::class, 'index'])->name('api.v1.schools.index');
        Route::post('/schools', [SchoolController::class, 'store'])->name('api.v1.schools.store');
        Route::get('/schools/{schoolId}', [SchoolController::class, 'show'])->name('api.v1.schools.show');
        Route::patch('/schools/{schoolId}', [SchoolController::class, 'update'])->name('api.v1.schools.update');

        Route::middleware('schoolmaster.school_context')->group(function (): void {
            Route::get('/permissions', [PermissionController::class, 'index'])->name('api.v1.permissions.index');

            Route::get('/roles', [RoleController::class, 'index'])->name('api.v1.roles.index');
            Route::post('/roles', [RoleController::class, 'store'])->name('api.v1.roles.store');

            Route::get('/users', [UserController::class, 'index'])->name('api.v1.users.index');
            Route::post('/users', [UserController::class, 'store'])->name('api.v1.users.store');

            Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('api.v1.academic-years.index');
            Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('api.v1.academic-years.store');

            Route::get('/academic-periods', [AcademicPeriodController::class, 'index'])->name('api.v1.academic-periods.index');
            Route::post('/academic-periods', [AcademicPeriodController::class, 'store'])->name('api.v1.academic-periods.store');

            Route::get('/guardians', [GuardianController::class, 'index'])->name('api.v1.guardians.index');
            Route::post('/guardians', [GuardianController::class, 'store'])->name('api.v1.guardians.store');

            Route::prefix('student-profiles')->middleware('schoolmaster.explicit_school_context')->name('api.v1.student-profiles.')->group(function (): void {
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
