<x-mail::message>
# Set or reset your SchoolMaster password

Hello {{ $recipientName }},

An administrator requested a secure password link for your active SchoolMaster account.

<x-mail::button :url="$passwordUrl">
Choose password
</x-mail::button>

This link expires {{ $expiresAt->toDayDateTimeString() }} UTC and can be used only once. If you were not expecting this message, you can ignore it.

Thanks,<br>
SchoolMaster
</x-mail::message>
