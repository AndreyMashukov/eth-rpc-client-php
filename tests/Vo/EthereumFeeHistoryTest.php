<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Vo;

use Amashukov\EthRpc\Vo\EthereumFeeHistory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EthereumFeeHistoryTest extends TestCase
{
    public function testFromArrayDecodesHexToDecimal(): void
    {
        $history = EthereumFeeHistory::fromArray([
            'oldestBlock'   => '0x10',
            'baseFeePerGas' => ['0x3b9aca00', '0x3b9aca00'],
            'gasUsedRatio'  => [0.5, 0.6],
            'reward'        => [['0x59682f00'], ['0x59682f00']],
        ]);

        self::assertSame('16', $history->oldestBlock);
        self::assertSame(['1000000000', '1000000000'], $history->baseFeePerGas);
        self::assertSame([0.5, 0.6], $history->gasUsedRatio);
        self::assertSame([['1500000000'], ['1500000000']], $history->reward);
    }

    public function testDecodeGweiMedian(): void
    {
        $history = EthereumFeeHistory::fromArray([
            'oldestBlock'   => '0x1',
            'baseFeePerGas' => ['0x3b9aca00', '0x3b9aca00'],
            'gasUsedRatio'  => [0.5],
            'reward'        => [['0x59682f00'], ['0x59682f00'], ['0x59682f00']],
        ]);

        self::assertSame(2.5, $history->decodeGweiMedian());
    }

    public function testDecodeGweiMedianThrowsOnEmptyBaseFee(): void
    {
        $history = EthereumFeeHistory::fromArray(['oldestBlock' => '0x1', 'reward' => [['0x1']]]);

        $this->expectException(RuntimeException::class);
        $history->decodeGweiMedian();
    }

    public function testDecodeGweiMedianSkipsEmptyRewardRows(): void
    {
        $history = EthereumFeeHistory::fromArray([
            'oldestBlock'   => '0x1',
            'baseFeePerGas' => ['0x3b9aca00'],
            'gasUsedRatio'  => [],
            'reward'        => [[], ['0x59682f00']],
        ]);

        self::assertSame(2.5, $history->decodeGweiMedian());
    }
}
