<?php

use App\Exceptions\AuthLockoutException;
use App\Exceptions\ConflictException;
use App\Exceptions\InactiveRecordException;
use App\Exceptions\OutputExpiredException;
use App\Exceptions\PermissionDeniedException;
use App\Exceptions\TenantContextException;
use App\Exceptions\TokenRejectedException;
use App\Http\Middleware\AuthenticateBearerToken;
use App\Http\Middleware\ResolveSchoolContext;
use App\Http\Resources\ApiResponse;
use App\Services\Assessment\AssessmentAuditService;
use App\Services\ClassroomRoster\ClassroomRosterFailureAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'schoolmaster.auth' => AuthenticateBearerToken::class,
            'schoolmaster.school_context' => ResolveSchoolContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, \Illuminate\Http\Request $request) {
            app(AssessmentAuditService::class)->recordRequestFailure($request, 'validation', 'rejected', 'validation_failed');

            return ApiResponse::validation($exception->errors());
        });

        $exceptions->render(function (AuthenticationException $exception) {
            return ApiResponse::unauthorized($exception->getMessage() ?: 'Authentication is missing or invalid.');
        });

        $exceptions->render(function (AuthorizationException $exception, \Illuminate\Http\Request $request) {
            app(ClassroomRosterFailureAudit::class)->record($request, 'forbidden', [
                'failure_type' => 'authorization',
            ]);
            app(AssessmentAuditService::class)->recordRequestFailure($request, 'denial', 'denied', 'authorization_denied');

            return ApiResponse::forbidden($exception->getMessage() ?: 'The authenticated user lacks permission for this action.');
        });

        $exceptions->render(function (AccessDeniedHttpException $exception, \Illuminate\Http\Request $request) {
            app(ClassroomRosterFailureAudit::class)->record($request, 'forbidden', [
                'failure_type' => 'access_denied',
            ]);
            app(AssessmentAuditService::class)->recordRequestFailure($request, 'denial', 'denied', 'access_denied');

            return ApiResponse::forbidden($exception->getMessage() ?: 'The authenticated user lacks permission for this action.');
        });

        $exceptions->render(function (PermissionDeniedException $exception, \Illuminate\Http\Request $request) {
            app(ClassroomRosterFailureAudit::class)->record($request, 'forbidden', [
                'failure_type' => 'permission_denied',
            ]);
            app(AssessmentAuditService::class)->recordRequestFailure($request, 'denial', 'denied', 'permission_denied');

            return ApiResponse::forbidden($exception->getMessage() ?: 'The authenticated user lacks permission for this action.');
        });

        $exceptions->render(function (OutputExpiredException $exception) {
            return ApiResponse::outputExpired($exception->getMessage());
        });

        $exceptions->render(function (ConflictException $exception, \Illuminate\Http\Request $request) {
            app(ClassroomRosterFailureAudit::class)->record($request, 'conflict', [
                'failure_type' => 'conflict',
            ]);
            app(AssessmentAuditService::class)->recordRequestFailure($request, 'conflict', 'conflicted', 'lifecycle_conflict');

            return ApiResponse::error('conflict', $exception->getMessage(), [], 409);
        });

        $exceptions->render(function (TenantContextException $exception, \Illuminate\Http\Request $request) {
            app(ClassroomRosterFailureAudit::class)->record($request, 'tenant_mismatch', [
                'failure_type' => 'tenant_context',
            ]);
            app(AssessmentAuditService::class)->recordRequestFailure($request, 'denial', 'denied', 'tenant_context');

            return ApiResponse::tenantMismatch($exception->getMessage());
        });

        $exceptions->render(function (InactiveRecordException $exception) {
            return ApiResponse::inactiveRecord($exception->getMessage());
        });

        $exceptions->render(function (AuthLockoutException $exception) {
            return ApiResponse::lockout($exception->retryAfterSeconds());
        });

        $exceptions->render(function (TokenRejectedException $exception) {
            return ApiResponse::tokenRejected($exception->reasonCode(), $exception->getMessage());
        });

        $exceptions->render(function (ThrottleRequestsException $exception) {
            return ApiResponse::lockout((int) ($exception->getHeaders()['Retry-After'] ?? 900));
        });

        $exceptions->render(function (HttpException $exception) {
            if ($exception->getStatusCode() === 423) {
                return ApiResponse::error('scan_pending', $exception->getMessage(), [], 423);
            }

            if ($exception->getStatusCode() === 424) {
                return ApiResponse::error('scan_failed', $exception->getMessage(), [], 424);
            }

            return null;
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $exception) {
            return ApiResponse::notFound();
        });
    })->create();
