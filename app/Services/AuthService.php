<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\AuditEventData;
use App\Exceptions\TokenRejectedException;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    public function __construct(
        private readonly AuthTokenLifecycleService $tokens,
        private readonly LoginAttemptControlService $attempts,
        private readonly AuditEventService $audit,
        private readonly TenantContextResolver $tenantContextResolver,
    ) {}

    /**
     * @param  array{email: string, password: string, school_id?: string|null}  $credentials
     */
    public function login(array $credentials, Request $request): array
    {
        $email = strtolower($credentials['email']);
        $ip = $request->ip() ?? 'unknown';

        $this->attempts->assertNotLocked($email, $ip);

        /** @var User|null $user */
        $user = User::query()->with(['school', 'roles.permissions'])->where('email', $email)->first();
        $school = isset($credentials['school_id']) && $credentials['school_id'] !== null
            ? School::query()->where('uuid', $credentials['school_id'])->first()
            : null;

        if (
            $user === null
            || ! Hash::check($credentials['password'], $user->password)
            || $user->status !== 'active'
            || (array_key_exists('school_id', $credentials) && $credentials['school_id'] !== null && $school === null)
            || ($school !== null && $user->school_id !== $school->id)
            || ($user->school !== null && $user->school->status !== 'active')
        ) {
            $this->attempts->recordFailure($email, $ip);
            $this->audit->record(new AuditEventData('login_failure', 'failure', sourceIp: $ip, metadata: ['email' => $email]));

            throw new AuthenticationException('Authentication is missing or invalid.');
        }

        $this->attempts->clear($email, $ip);
        [$plainToken, $expiresAt] = $this->tokens->issue($user);

        $this->audit->record(new AuditEventData(
            eventType: 'login_success',
            outcome: 'success',
            actorUserId: $user->id,
            schoolId: $user->school_id,
            sourceIp: $ip,
        ));

        return [$user, $plainToken, $expiresAt];
    }

    public function currentUser(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->attributes->get('auth_user');

        if ($user === null) {
            throw new TokenRejectedException('unauthorized', 'Authentication is missing or invalid.');
        }

        $this->tenantContextResolver->resolve($request, $user->loadMissing('school'));

        return $user->loadMissing(['school', 'roles.permissions']);
    }

    public function logout(Request $request): void
    {
        $token = $this->tokens->resolve($request->bearerToken());
        $this->tokens->revoke($token);

        $this->audit->record(new AuditEventData(
            eventType: 'logout',
            outcome: 'success',
            actorUserId: $token->user_id,
            schoolId: $token->school_id,
            sourceIp: $request->ip(),
        ));
    }
}
