<?php

declare(strict_types=1);

namespace App\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class AccountInvitationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $setupUrl,
        public readonly CarbonInterface $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Set up your SchoolMaster account');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.account-invitation');
    }
}
