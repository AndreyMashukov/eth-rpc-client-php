<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Vo;

use Amashukov\EthRpc\Vo\EthereumTransactionReceipt;
use Amashukov\EthRpc\Vo\EthereumTransactionStatus;
use PHPUnit\Framework\TestCase;

final class EthereumTransactionReceiptTest extends TestCase
{
    public function testNullReceiptIsPending(): void
    {
        $receipt = EthereumTransactionReceipt::fromArray('0xhash', null);

        self::assertSame(EthereumTransactionStatus::Pending, $receipt->status);
        self::assertTrue($receipt->isStatusPending());
        self::assertFalse($receipt->isStatusSuccess());
        self::assertSame([], $receipt->logs);
        self::assertNull($receipt->fee);
    }

    public function testSuccessReceiptComputesFeeAndLogs(): void
    {
        $receipt = EthereumTransactionReceipt::fromArray('0xhash', [
            'status'            => '0x1',
            'blockNumber'       => '0x10',
            'blockHash'         => '0xblk',
            'transactionIndex'  => '0x1',
            'from'              => '0xAAA',
            'to'                => '0xBBB',
            'gasUsed'           => '0x5208',
            'effectiveGasPrice' => '0x3b9aca00',
            'type'              => '0x2',
            'logs'              => [['address' => '0xCCC', 'topics' => [], 'data' => '0x'], 'not-an-array'],
        ]);

        self::assertTrue($receipt->isStatusSuccess());
        self::assertSame('21000', $receipt->gasUsed);
        self::assertSame('1000000000', $receipt->effectiveGasPrice);
        self::assertSame('21000000000000', $receipt->fee);
        self::assertSame('0xaaa', $receipt->from);
        self::assertSame('0xbbb', $receipt->to);
        self::assertCount(1, $receipt->logs);
        self::assertSame('0xccc', $receipt->logs[0]->address);
    }

    public function testFailedReceipt(): void
    {
        $receipt = EthereumTransactionReceipt::fromArray('0xhash', ['status' => '0x0'], 'execution reverted');

        self::assertTrue($receipt->isStatusFail());
        self::assertSame('execution reverted', $receipt->revertReason);
    }

    public function testNumericStatusForm(): void
    {
        $success = EthereumTransactionReceipt::fromArray('0xhash', ['status' => 1]);
        $failure = EthereumTransactionReceipt::fromArray('0xhash', ['status' => 0]);

        self::assertTrue($success->isStatusSuccess());
        self::assertTrue($failure->isStatusFail());
    }
}
