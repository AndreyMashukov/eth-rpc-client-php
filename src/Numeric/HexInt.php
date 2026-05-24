<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Numeric;

use InvalidArgumentException;

final class HexInt
{
    /**
     * @throws InvalidArgumentException on malformed input or overflow
     */
    public static function fromHex(string $hex): int
    {
        if ('' === $hex) {
            throw new InvalidArgumentException('HexInt::fromHex: empty input');
        }

        $body = $hex;
        if (str_starts_with($body, '0x') || str_starts_with($body, '0X')) {
            $body = substr($body, 2);
        }

        if ('' === $body) {
            throw new InvalidArgumentException(sprintf('HexInt::fromHex: empty hex body in %s', $hex));
        }

        if (1 !== preg_match('/^[0-9a-fA-F]+$/', $body)) {
            throw new InvalidArgumentException(sprintf('HexInt::fromHex: non-hex characters in %s', $hex));
        }

        if (strlen($body) > 15) {
            $value = hexdec($body);
            if (!is_int($value)) {
                throw new InvalidArgumentException(sprintf('HexInt::fromHex: value %s exceeds PHP_INT_MAX — use HexBig', $hex));
            }

            return $value;
        }

        return (int) hexdec($body);
    }

    /**
     * @throws InvalidArgumentException on negative input
     */
    public static function toHex(int $value): string
    {
        if ($value < 0) {
            throw new InvalidArgumentException(sprintf('HexInt::toHex: negative input %d', $value));
        }

        return '0x' . dechex($value);
    }

    /**
     * @throws InvalidArgumentException on negative input
     */
    public static function toHex32(int $value): string
    {
        if ($value < 0) {
            throw new InvalidArgumentException(sprintf('HexInt::toHex32: negative input %d', $value));
        }

        return '0x' . str_pad(dechex($value), 64, '0', \STR_PAD_LEFT);
    }
}
