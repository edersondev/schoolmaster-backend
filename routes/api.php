<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SchoolController;
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
    });
});
