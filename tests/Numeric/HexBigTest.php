<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Numeric;

use Amashukov\EthRpc\Numeric\HexBig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class HexBigTest extends TestCase
{
    public function testFromHexBeyondIntMax(): void
    {
        self::assertSame('1000000000000000000', HexBig::fromHex('0x0de0b6b3a7640000'));
        self::assertSame('0', HexBig::fromHex('0x0'));
    }

    public function testToHexRoundTrip(): void
    {
        self::assertSame('0xde0b6b3a7640000', HexBig::toHex('1000000000000000000'));
        self::assertSame('0x0', HexBig::toHex('0'));
    }

    public function testToHex32LeftPads(): void
    {
        self::assertSame('0x' . str_pad('de0b6b3a7640000', 64, '0', \STR_PAD_LEFT), HexBig::toHex32('1000000000000000000'));
    }

    public function testFromHexNonHexThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HexBig::fromHex('0xqq');
    }

    public function testToHexNegativeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HexBig::toHex('-5');
    }

    public function testToHex32OverflowThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HexBig::toHex32(str_repeat('9', 100));
    }
}
