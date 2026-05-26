<?php

declare(strict_types=1);

namespace App\Services\AdministrationLifecycle;

final class LifecycleAction
{
    public const ACTIVATE = 'activate';

    public const DEACTIVATE = 'deactivate';

    public const DELETE = 'delete';

    public const RESTORE = 'restore';

    public const UPDATED = 'updated';

    public const ACTIVATED = 'activated';

    public const DEACTIVATED = 'deactivated';

    public const DELETED = 'deleted';

    public const RESTORED = 'restored';

    public const BULK_LIFECYCLE = 'bulk_lifecycle';

    public const MAX_BULK_RECORDS = 50;

    public static function operationForAction(string $action): string
    {
        return match ($action) {
            self::ACTIVATE => self::ACTIVATED,
            self::DEACTIVATE => self::DEACTIVATED,
            self::DELETE => self::DELETED,
            self::RESTORE => self::RESTORED,
            default => $action,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return [self::ACTIVATE, self::DEACTIVATE, self::DELETE, self::RESTORE];
    }
}
