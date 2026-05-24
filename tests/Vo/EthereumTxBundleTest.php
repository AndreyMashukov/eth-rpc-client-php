<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Vo;

use Amashukov\EthRpc\Vo\EthereumTransaction;
use Amashukov\EthRpc\Vo\EthereumTransactionReceipt;
use Amashukov\EthRpc\Vo\EthereumTxBundle;
use PHPUnit\Framework\TestCase;

final class EthereumTxBundleTest extends TestCase
{
    public function testPredicatesDelegateToReceipt(): void
    {
        $bundle = new EthereumTxBundle(
            transaction: EthereumTransaction::fromArray('0xhash', ['from' => '0xabc', 'value' => '0x0']),
            receipt: EthereumTransactionReceipt::fromArray('0xhash', ['status' => '0x1']),
        );

        self::assertTrue($bundle->isStatusSuccess());
        self::assertFalse($bundle->isStatusFail());
        self::assertFalse($bundle->isStatusPending());
        self::assertSame('0xhash', $bundle->transaction->hash);
    }

    public function testPendingBundle(): void
    {
        $bundle = new EthereumTxBundle(
            transaction: EthereumTransaction::fromArray('0xhash', null),
            receipt: EthereumTransactionReceipt::fromArray('0xhash', null),
        );

        self::assertTrue($bundle->isStatusPending());
    }
}
