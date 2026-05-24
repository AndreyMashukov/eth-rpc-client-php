<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Vo;

use Amashukov\EthRpc\Vo\EthereumTransaction;
use Amashukov\EthRpc\Vo\EthereumTransactionType;
use PHPUnit\Framework\TestCase;

final class EthereumTransactionTest extends TestCase
{
    public function testNullRowIsEmptyLegacyEnvelope(): void
    {
        $tx = EthereumTransaction::fromArray('0xhash', null);

        self::assertSame('0xhash', $tx->hash);
        self::assertSame('', $tx->from);
        self::assertNull($tx->to);
        self::assertSame('0', $tx->value);
        self::assertNull($tx->nonce);
        self::assertSame(EthereumTransactionType::Legacy, $tx->type);
    }

    public function testEip1559Transaction(): void
    {
        $tx = EthereumTransaction::fromArray('0xhash', [
            'from'                 => '0xAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            'to'                   => '0xBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB',
            'value'                => '0x0de0b6b3a7640000',
            'nonce'                => '0x5',
            'gas'                  => '0x5208',
            'maxFeePerGas'         => '0x3b9aca00',
            'maxPriorityFeePerGas' => '0x59682f00',
            'chainId'              => '0x1',
            'input'                => '0xabcdef',
            'type'                 => '0x2',
        ]);

        self::assertSame('0xaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', $tx->from);
        self::assertSame('0xbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb', $tx->to);
        self::assertSame('1000000000000000000', $tx->value);
        self::assertSame(5, $tx->nonce);
        self::assertSame('21000', $tx->gasLimit);
        self::assertSame('1000000000', $tx->maxFeePerGas);
        self::assertSame('1500000000', $tx->maxPriorityFeePerGas);
        self::assertSame('1', $tx->chainId);
        self::assertSame('0xabcdef', $tx->data);
        self::assertSame(EthereumTransactionType::Eip1559, $tx->type);
    }

    public function testContractCreationHasNullTo(): void
    {
        $tx = EthereumTransaction::fromArray('0xhash', ['from' => '0xabc', 'value' => '0x0']);

        self::assertNull($tx->to);
        self::assertSame(EthereumTransactionType::Legacy, $tx->type);
    }

    public function testUnknownTransactionTypeFallsBackToLegacy(): void
    {
        $tx = EthereumTransaction::fromArray("0xhash", ["from" => "0xabc", "value" => "0x0", "type" => "0x63"]);

        self::assertSame(EthereumTransactionType::Legacy, $tx->type);
    }
}
