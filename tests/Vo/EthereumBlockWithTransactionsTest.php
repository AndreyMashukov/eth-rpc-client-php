<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Vo;

use Amashukov\EthRpc\Vo\EthereumBlockWithTransactions;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class EthereumBlockWithTransactionsTest extends TestCase
{
    public function testItKeepsTheBlockAndEveryTransactionItCarries(): void
    {
        $block = EthereumBlockWithTransactions::fromArray([
            'number'       => '0x10',
            'hash'         => '0x' . str_repeat('a', 64),
            'parentHash'   => '0x' . str_repeat('b', 64),
            'timestamp'    => '0x5',
            'transactions' => [
                ['hash' => '0x' . str_repeat('1', 64), 'from' => '0xAAAA', 'to' => '0xBBBB', 'value' => '0xde0b6b3a7640000', 'input' => '0x'],
                ['hash' => '0x' . str_repeat('2', 64), 'from' => '0xCCCC', 'to' => null, 'value' => '0x0', 'input' => '0x6080'],
            ],
        ]);

        self::assertNotNull($block);
        self::assertSame('16', $block->block->number);
        self::assertCount(2, $block->transactions);
        self::assertSame('0x' . str_repeat('1', 64), $block->transactions[0]->hash);
        self::assertSame('0xbbbb', $block->transactions[0]->to);
        self::assertSame('1000000000000000000', $block->transactions[0]->value);
        self::assertNull($block->transactions[1]->to, 'a contract creation has no recipient');
    }

    public function testAMissingBlockIsNull(): void
    {
        self::assertNull(EthereumBlockWithTransactions::fromArray(null));
    }

    public function testABlockWithoutTransactionsHasAnEmptyList(): void
    {
        $block = EthereumBlockWithTransactions::fromArray(['number' => '0x1', 'hash' => '0x', 'parentHash' => '0x', 'timestamp' => '0x0', 'transactions' => []]);

        self::assertNotNull($block);
        self::assertSame([], $block->transactions);
    }

    public function testHashOnlyTransactionsAreRefused(): void
    {
        $this->expectException(UnexpectedValueException::class);

        EthereumBlockWithTransactions::fromArray(['number' => '0x1', 'hash' => '0x', 'parentHash' => '0x', 'timestamp' => '0x0', 'transactions' => ['0x' . str_repeat('1', 64)]]);
    }
}
