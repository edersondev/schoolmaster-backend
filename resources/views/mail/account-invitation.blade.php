<x-mail::message>
# Set up your SchoolMaster account

Hello {{ $recipientName }},

An administrator invited you to SchoolMaster. Use the secure link below to choose your password and activate your account.

<x-mail::button :url="$setupUrl">
Set up account
</x-mail::button>

This invitation expires {{ $expiresAt->toDayDateTimeString() }} UTC and can be used only once. If you were not expecting this invitation, you can ignore this email.

Thanks,
SchoolMaster
</x-mail::message>
