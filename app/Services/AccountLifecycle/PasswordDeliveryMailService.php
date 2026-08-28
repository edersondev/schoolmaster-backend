<?php

declare(strict_types=1);

namespace App\Services\AccountLifecycle;

use App\Exceptions\PasswordDeliveryException;
use App\Mail\PasswordDeliveryMail;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class PasswordDeliveryMailService
{
    public function send(User $recipient, string $plainToken, CarbonInterface $expiresAt, string $purpose): void
    {
        try {
            Mail::to($recipient->email)->send(new PasswordDeliveryMail(
                recipientName: $recipient->full_name ?? $recipient->name,
                passwordUrl: $this->passwordUrl($plainToken),
                expiresAt: $expiresAt,
                purpose: $purpose,
            ));
        } catch (PasswordDeliveryException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new PasswordDeliveryException(
                'Password email could not be submitted. Try again.',
                previous: $exception,
            );
        }
    }

    private function passwordUrl(string $plainToken): string
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
            throw new PasswordDeliveryException('Password email could not be submitted. Try again.');
        }

        return $origin.'/auth/password-resets#token='.rawurlencode($plainToken);
    }
}
