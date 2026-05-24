<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Vo;

final class Wire
{
    public static function nullableStr(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        return null;
    }

    public static function str(mixed $value, string $default = ''): string
    {
        return self::nullableStr($value) ?? $default;
    }
}
