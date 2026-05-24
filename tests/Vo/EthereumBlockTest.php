<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Vo;

use Amashukov\EthRpc\Vo\EthereumBlock;
use PHPUnit\Framework\TestCase;

final class EthereumBlockTest extends TestCase
{
    public function testNullRowReturnsNull(): void
    {
        self::assertNull(EthereumBlock::fromArray(null));
    }

    public function testPost1559Block(): void
    {
        $block = EthereumBlock::fromArray([
            'number'        => '0x10d4f',
            'hash'          => '0xabc',
            'parentHash'    => '0xdef',
            'timestamp'     => '0x655f2d00',
            'baseFeePerGas' => '0x3b9aca00',
            'gasUsed'       => '0x5208',
            'gasLimit'      => '0x1c9c380',
        ]);

        if (!$block instanceof EthereumBlock) {
            self::fail('expected a block');
        }
        self::assertSame('68943', $block->number);
        self::assertSame('0xabc', $block->hash);
        self::assertSame('0xdef', $block->parentHash);
        self::assertSame(1_700_736_256, $block->timestamp);
        self::assertSame('1000000000', $block->baseFeePerGas);
        self::assertSame('21000', $block->gasUsed);
        self::assertSame('30000000', $block->gasLimit);
    }

    public function testPendingBlockHasNullNumberAndBaseFee(): void
    {
        $block = EthereumBlock::fromArray(['hash' => '0x0', 'parentHash' => '0x1']);

        if (!$block instanceof EthereumBlock) {
            self::fail('expected a block');
        }
        self::assertNull($block->number);
        self::assertNull($block->baseFeePerGas);
        self::assertSame(0, $block->timestamp);
    }
}
