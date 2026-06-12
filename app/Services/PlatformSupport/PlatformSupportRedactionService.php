<?php

declare(strict_types=1);

namespace App\Services\PlatformSupport;

final class PlatformSupportRedactionService
{
    public const PROTECTED_COUNT_THRESHOLD = 5;

    /**
     * @return array{value: int|null, suppressed: bool}
     */
    public function protectedCount(?int $count): array
    {
        if ($count !== null && $count > 0 && $count < self::PROTECTED_COUNT_THRESHOLD) {
            return ['value' => null, 'suppressed' => true];
        }

        return ['value' => $count, 'suppressed' => false];
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, string|int|float|bool|null>
     */
    public function auditMetadata(array $metadata): array
    {
        $blockedKeys = [
            'authorization',
            'bearer',
            'content',
            'credential',
            'download',
            'file',
            'guardian',
            'output',
            'password',
            'path',
            'private',
            'raw',
            'record',
            'report_output',
            'student',
            'teacher',
            'token',
        ];

        $safe = [];

        foreach ($metadata as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            foreach ($blockedKeys as $blockedKey) {
                if (str_contains($normalizedKey, $blockedKey)) {
                    continue 2;
                }
            }

            if (is_string($value) || is_int($value) || is_float($value) || is_bool($value) || $value === null) {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }
}
