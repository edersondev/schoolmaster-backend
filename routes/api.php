<?php

use App\Http\Controllers\Api\V1\AcademicPeriodController;
use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GuardianController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SchoolController;
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
        });
    });
});
