<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Numeric;

use Amashukov\EthRpc\Numeric\Wei;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WeiTest extends TestCase
{
    public function testFromHex(): void
    {
        self::assertSame('1000000000000000000', Wei::fromHex('0x0de0b6b3a7640000'));
    }

    public function testToEthFloat(): void
    {
        self::assertSame(1.0, Wei::toEthFloat('1000000000000000000'));
        self::assertSame(0.0, Wei::toEthFloat('0'));
        self::assertSame(1.5, Wei::toEthFloat('1500000000000000000'));
    }

    public function testToUnitsFloatForUsdt(): void
    {
        self::assertSame(12.5, Wei::toUnitsFloat('12500000', Wei::USDT_DECIMALS));
        self::assertSame(0.0, Wei::toUnitsFloat('0', Wei::USDT_DECIMALS));
    }

    public function testToEthFloatNonNumericThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Wei::toEthFloat('abc');
    }

    public function testToUnitsFloatNegativeDecimalsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Wei::toUnitsFloat('100', -1);
    }
}
