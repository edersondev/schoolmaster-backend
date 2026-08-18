<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use App\Exceptions\InvitationDeliveryException;
use App\Mail\AccountInvitationMail;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class AccountInvitationDeliveryService
{
    public function send(User $recipient, string $plainToken, CarbonInterface $expiresAt): void
    {
        try {
            Mail::to($recipient->email)->send(new AccountInvitationMail(
                recipientName: $recipient->full_name ?? $recipient->name,
                setupUrl: $this->setupUrl($plainToken),
                expiresAt: $expiresAt,
            ));
        } catch (InvitationDeliveryException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvitationDeliveryException(
                'Invitation email could not be submitted. Try again.',
                previous: $exception,
            );
        }
    }

    private function setupUrl(string $plainToken): string
    {
        $origin = rtrim((string) config('app.frontend_url'), '/');
        $parts = parse_url($origin);

        if (
            $parts === false
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || ! in_array($parts['path'] ?? '', ['', '/'], true)
        ) {
            throw new InvitationDeliveryException(
                'Invitation email could not be submitted. Try again.',
            );
        }

        return $origin.'/auth/account-invitations/setup#token='.rawurlencode($plainToken);
    }
}
