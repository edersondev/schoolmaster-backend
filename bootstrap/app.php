<?php

use App\Exceptions\AuthLockoutException;
use App\Exceptions\InactiveRecordException;
use App\Exceptions\TenantContextException;
use App\Exceptions\TokenRejectedException;
use App\Http\Middleware\AuthenticateBearerToken;
use App\Http\Middleware\ResolveSchoolContext;
use App\Http\Resources\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
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
        $exceptions->render(function (ValidationException $exception) {
            return ApiResponse::validation($exception->errors());
        });

        $exceptions->render(function (AuthenticationException $exception) {
            return ApiResponse::unauthorized($exception->getMessage() ?: 'Authentication is missing or invalid.');
        });

        $exceptions->render(function (AuthorizationException $exception) {
            return ApiResponse::forbidden($exception->getMessage() ?: 'The authenticated user lacks permission for this action.');
        });

        $exceptions->render(function (TenantContextException $exception) {
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

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $exception) {
            return ApiResponse::notFound();
        });
    })->create();
