<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Vo;

use Amashukov\EthRpc\Vo\EthereumTransactionStatus;
use Amashukov\EthRpc\Vo\EthereumTransactionType;
use PHPUnit\Framework\TestCase;

final class EthereumEnumsTest extends TestCase
{
    public function testStatusBackingValuesPerEip658(): void
    {
        self::assertSame(-1, EthereumTransactionStatus::Pending->value);
        self::assertSame(0, EthereumTransactionStatus::Failure->value);
        self::assertSame(1, EthereumTransactionStatus::Success->value);
    }

    public function testTypeBackingValuesPerEip2718(): void
    {
        self::assertSame(0, EthereumTransactionType::Legacy->value);
        self::assertSame(1, EthereumTransactionType::Eip2930->value);
        self::assertSame(2, EthereumTransactionType::Eip1559->value);
        self::assertSame(3, EthereumTransactionType::Eip4844->value);
        self::assertSame(4, EthereumTransactionType::Eip7702->value);
    }

    public function testTypeTryFromKnownValue(): void
    {
        self::assertSame(EthereumTransactionType::Eip1559, EthereumTransactionType::tryFrom(2));
    }
}
