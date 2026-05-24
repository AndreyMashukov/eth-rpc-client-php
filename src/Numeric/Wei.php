<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Numeric;

use InvalidArgumentException;

final class Wei
{
    public const int ETH_DECIMALS = 18;

    public const int USDT_DECIMALS = 6;

    /**
     * @return numeric-string decimal wei value
     */
    public static function fromHex(string $hexWei): string
    {
        return HexBig::fromHex($hexWei);
    }

    public static function toEthFloat(string $wei): float
    {
        if ('' === $wei || '0' === $wei) {
            return 0.0;
        }

        if (1 !== preg_match('/^\d+$/', $wei)) {
            throw new InvalidArgumentException(sprintf('Wei::toEthFloat: non-numeric input %s', $wei));
        }

        return (float) bcdiv($wei, bcpow('10', (string) self::ETH_DECIMALS), 18);
    }

    public static function toUnitsFloat(string $smallestUnit, int $decimals): float
    {
        if ('' === $smallestUnit || '0' === $smallestUnit) {
            return 0.0;
        }

        if (1 !== preg_match('/^\d+$/', $smallestUnit)) {
            throw new InvalidArgumentException(sprintf('Wei::toUnitsFloat: non-numeric input %s', $smallestUnit));
        }

        if ($decimals < 0) {
            throw new InvalidArgumentException(sprintf('Wei::toUnitsFloat: negative decimals %d', $decimals));
        }

        return (float) bcdiv($smallestUnit, bcpow('10', (string) $decimals), max($decimals, 18));
    }
}
