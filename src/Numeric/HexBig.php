<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Numeric;

use InvalidArgumentException;

final class HexBig
{
    /**
     * @throws InvalidArgumentException on malformed input
     *
     * @return numeric-string
     */
    public static function fromHex(string $hex): string
    {
        if ('' === $hex) {
            throw new InvalidArgumentException('HexBig::fromHex: empty input');
        }

        $body = $hex;
        if (str_starts_with($body, '0x') || str_starts_with($body, '0X')) {
            $body = substr($body, 2);
        }

        if ('' === $body) {
            throw new InvalidArgumentException(sprintf('HexBig::fromHex: empty hex body in %s', $hex));
        }

        if (1 !== preg_match('/^[0-9a-fA-F]+$/', $body)) {
            throw new InvalidArgumentException(sprintf('HexBig::fromHex: non-hex characters in %s', $hex));
        }

        $decimal = gmp_strval(gmp_init($body, 16));
        if (!is_numeric($decimal)) {
            throw new InvalidArgumentException(sprintf('HexBig::fromHex: gmp produced a non-numeric string from %s', $hex));
        }

        return $decimal;
    }

    /**
     * @throws InvalidArgumentException on non-numeric or negative input
     */
    public static function toHex(string $decimal): string
    {
        if ('' === $decimal || '0' === $decimal) {
            return '0x0';
        }

        if (1 !== preg_match('/^\d+$/', $decimal)) {
            throw new InvalidArgumentException(sprintf('HexBig::toHex: non-numeric or negative input %s', $decimal));
        }

        return '0x' . gmp_strval(gmp_init($decimal, 10), 16);
    }

    /**
     * @throws InvalidArgumentException on non-numeric / negative / >32-byte input
     */
    public static function toHex32(string $decimal): string
    {
        if ('' === $decimal || 1 !== preg_match('/^\d+$/', $decimal)) {
            throw new InvalidArgumentException(sprintf('HexBig::toHex32: non-numeric or negative input %s', $decimal));
        }
        $hex = gmp_strval(gmp_init($decimal, 10), 16);
        if (\strlen($hex) > 64) {
            throw new InvalidArgumentException(sprintf('HexBig::toHex32: value exceeds 256 bits (%s)', $decimal));
        }

        return '0x' . str_pad($hex, 64, '0', \STR_PAD_LEFT);
    }
}
