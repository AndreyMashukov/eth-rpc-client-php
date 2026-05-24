<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Numeric;

use Amashukov\EthRpc\Numeric\HexInt;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class HexIntTest extends TestCase
{
    public function testFromHexDecodesPrefixedAndBare(): void
    {
        self::assertSame(255, HexInt::fromHex('0xff'));
        self::assertSame(255, HexInt::fromHex('ff'));
        self::assertSame(0, HexInt::fromHex('0x0'));
        self::assertSame(68_656, HexInt::fromHex('0x10c30'));
    }

    public function testToHexAndToHex32(): void
    {
        self::assertSame('0xff', HexInt::toHex(255));
        self::assertSame('0x0', HexInt::toHex(0));
        self::assertSame('0x' . str_pad('ff', 64, '0', \STR_PAD_LEFT), HexInt::toHex32(255));
    }

    public function testEmptyInputThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HexInt::fromHex('');
    }

    public function testNonHexThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HexInt::fromHex('0xzz');
    }

    public function testOverflowThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HexInt::fromHex('0xffffffffffffffffff');
    }

    public function testNegativeToHexThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        HexInt::toHex(-1);
    }
}
