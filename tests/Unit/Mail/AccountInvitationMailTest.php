<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\AccountInvitationMail;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class AccountInvitationMailTest extends TestCase
{
    public function test_it_renders_product_action_expiry_and_escaped_recipient_name(): void
    {
        $expiresAt = Carbon::parse('2026-08-22T12:00:00Z')->toImmutable();
        $mail = new AccountInvitationMail(
            recipientName: 'Invited <script>alert(1)</script>',
            setupUrl: 'https://app.schoolmaster.test/auth/account-invitations/plain-token/setup',
            expiresAt: $expiresAt,
        );

        $html = $mail->render();

        $this->assertStringContainsString('SchoolMaster', $html);
        $this->assertStringContainsString('Set up account', $html);
        $this->assertStringContainsString($mail->setupUrl, $html);
        $this->assertStringContainsString($expiresAt->toDayDateTimeString(), $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }
}
