<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Vo;

use Amashukov\EthRpc\Vo\EthereumFeeData;
use PHPUnit\Framework\TestCase;

final class EthereumFeeDataTest extends TestCase
{
    public function testEip1559Shape(): void
    {
        $fee = new EthereumFeeData(gasPrice: null, maxFeePerGas: '4000000000', maxPriorityFeePerGas: '1500000000');

        self::assertNull($fee->gasPrice);
        self::assertSame('4000000000', $fee->maxFeePerGas);
        self::assertSame('1500000000', $fee->maxPriorityFeePerGas);
    }

    public function testLegacyShape(): void
    {
        $fee = new EthereumFeeData(gasPrice: '2000000000', maxFeePerGas: '2000000000', maxPriorityFeePerGas: '2000000000');

        self::assertSame('2000000000', $fee->gasPrice);
    }
}
