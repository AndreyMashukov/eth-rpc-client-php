<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Vo;

use Amashukov\EthRpc\Vo\EthereumTransactionLog;
use PHPUnit\Framework\TestCase;

final class EthereumTransactionLogTest extends TestCase
{
    public function testFromArrayFullRow(): void
    {
        $log = EthereumTransactionLog::fromArray([
            'address'          => '0xABCDEF0000000000000000000000000000000001',
            'topics'           => ['0xtopic0', '0xtopic1'],
            'data'             => '0xdeadbeef',
            'logIndex'         => '0x4',
            'removed'          => false,
            'transactionHash'  => '0xtxhash',
            'blockNumber'      => '0x10',
            'blockHash'        => '0xblockhash',
            'transactionIndex' => '0x2',
        ]);

        self::assertSame('0xabcdef0000000000000000000000000000000001', $log->address);
        self::assertSame(['0xtopic0', '0xtopic1'], $log->topics);
        self::assertSame('0xdeadbeef', $log->data);
        self::assertSame(4, $log->logIndex);
        self::assertFalse($log->removed);
        self::assertSame('0xtxhash', $log->transactionHash);
        self::assertSame('0x10', $log->blockNumber);
        self::assertSame('0xblockhash', $log->blockHash);
        self::assertSame(2, $log->transactionIndex);
    }

    public function testFromArrayDefaultsAndRemovedFlag(): void
    {
        $log = EthereumTransactionLog::fromArray(['removed' => true]);

        self::assertSame('', $log->address);
        self::assertSame([], $log->topics);
        self::assertSame('0x', $log->data);
        self::assertNull($log->logIndex);
        self::assertTrue($log->removed);
        self::assertNull($log->transactionHash);
        self::assertNull($log->transactionIndex);
    }
}
