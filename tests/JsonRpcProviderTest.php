<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests;

use Amashukov\EthRpc\Vo\EthereumBlock;
use Amashukov\EthRpc\Vo\EthereumTxBundle;
use Amashukov\AbiEncoder\AbiEncoder;
use Amashukov\EthRpc\BlockTag;
use Amashukov\EthRpc\EthRpcClientInterface;
use Amashukov\EthRpc\JsonRpcProvider;
use Amashukov\EthRpc\Tests\Support\FrozenClock;
use Amashukov\EthRpc\TransactionNotFoundException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JsonRpcProviderTest extends TestCase
{
    public function testGetBalanceConvertsHexToDecimal(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_getBalance')->willReturn('0x0de0b6b3a7640000');

        self::assertSame('1000000000000000000', $this->provider($client)->getBalance('0xabc'));
    }

    public function testGasPriceConvertsHexToDecimal(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_gasPrice')->willReturn('0x3b9aca00');

        self::assertSame('1000000000', $this->provider($client)->getGasPrice());
    }

    public function testScalarDelegations(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_blockNumber')->willReturn(123);
        $client->method('eth_chainId')->willReturn(1);
        $client->method('eth_getTransactionCount')->willReturn(7);
        $client->method('eth_getCode')->willReturn('0x60806040');

        $provider = $this->provider($client);
        self::assertSame(123, $provider->getBlockNumber());
        self::assertSame(1, $provider->getChainId());
        self::assertSame(7, $provider->getTransactionCount('0xabc'));
        self::assertSame('0x60806040', $provider->getCode('0xabc'));
    }

    public function testGetBlockWrapsTypedVo(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_getBlockByNumber')->willReturn(['number' => '0x10', 'hash' => '0xabc', 'parentHash' => '0xdef', 'timestamp' => '0x1']);

        $block = $this->provider($client)->getBlock('latest');
        if (!$block instanceof EthereumBlock) {
            self::fail('expected a block');
        }
        self::assertSame('16', $block->number);
    }

    public function testGetTypedTransactionCombinesReceiptAndTx(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_getTransactionReceipt')->willReturn(['status' => '0x1', 'blockNumber' => '0x10']);
        $client->method('eth_getTransactionByHash')->willReturn(['from' => '0xAAA', 'value' => '0x0']);

        $bundle = $this->provider($client)->getTypedTransaction('0xhash');

        self::assertTrue($bundle->isStatusSuccess());
        self::assertSame('0xaaa', $bundle->transaction->from);
    }

    public function testGetTypedTransactionThrowsWhenTransactionAbsent(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_getTransactionByHash')->willReturn(null);
        $client->method('eth_getTransactionReceipt')->willReturn(null);

        $this->expectException(TransactionNotFoundException::class);
        $this->provider($client)->getTypedTransaction('0xmissing');
    }

    public function testGetTypedTransactionSucceedsWhenReceiptPendingButTxPresent(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_getTransactionByHash')->willReturn(['from' => '0xAAA', 'value' => '0x0']);
        $client->method('eth_getTransactionReceipt')->willReturn(null);

        $bundle = $this->provider($client)->getTypedTransaction('0xpending');

        self::assertTrue($bundle->isStatusPending());
        self::assertTrue($bundle->transaction->isPresent());
    }

    public function testGetTransactionByHashTypedReportsPresence(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_getTransactionByHash')->willReturnOnConsecutiveCalls(
            ['from' => '0xAAA', 'value' => '0x0'],
            null,
        );

        $provider = $this->provider($client);
        self::assertTrue($provider->getTransactionByHashTyped('0xpresent')->isPresent());
        self::assertFalse($provider->getTransactionByHashTyped('0xabsent')->isPresent());
    }

    public function testGetTransactionReceiptTypedReturnsPendingOnNull(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_getTransactionReceipt')->willReturn(null);

        self::assertTrue($this->provider($client)->getTransactionReceiptTyped('0xhash')->isStatusPending());
    }

    public function testGetTypedLogsWrapsEachRow(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_getLogs')->willReturn([['address' => '0xABC', 'data' => '0x']]);

        $logs = $this->provider($client)->getTypedLogs(['address' => '0xabc']);

        self::assertCount(1, $logs);
        self::assertSame('0xabc', $logs[0]->address);
    }

    public function testGetNetworkUsesNameMapThenFallsBack(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_chainId')->willReturn(11_155_111);

        self::assertSame(['chainId' => 11_155_111, 'name' => 'sepolia'], $this->provider($client)->getNetwork([11_155_111 => 'sepolia']));
        self::assertSame(['chainId' => 11_155_111, 'name' => '11155111'], $this->provider($client)->getNetwork());
    }

    public function testGetErc20BalanceBuildsBalanceOfCalldata(): void
    {
        $expectedData = AbiEncoder::encodeCall('balanceOf(address)', [['address', '0x1111111111111111111111111111111111111111']]);

        $client = $this->createMock(EthRpcClientInterface::class);
        $client->expects($this->once())
            ->method('eth_call')
            ->with(self::callback(static fn(array $tx): bool => '0xtoken' === $tx['to'] && $expectedData === $tx['data']))
            ->willReturn('0x2a');

        self::assertSame('42', $this->provider($client)->getErc20Balance('0xtoken', '0x1111111111111111111111111111111111111111'));
    }

    public function testGetFeeDataEip1559(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_maxPriorityFeePerGas')->willReturn('0x59682f00');
        $client->method('eth_getBlockByNumber')->willReturn(['baseFeePerGas' => '0x3b9aca00', 'hash' => '0x', 'parentHash' => '0x']);

        $fee = $this->provider($client)->getFeeData();

        self::assertNull($fee->gasPrice);
        self::assertSame('3500000000', $fee->maxFeePerGas);
        self::assertSame('1500000000', $fee->maxPriorityFeePerGas);
    }

    public function testGetFeeDataLegacyFallback(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_maxPriorityFeePerGas')->willReturn('0x59682f00');
        $client->method('eth_getBlockByNumber')->willReturn(['hash' => '0x', 'parentHash' => '0x']);
        $client->method('eth_gasPrice')->willReturn('0x3b9aca00');

        $fee = $this->provider($client)->getFeeData();

        self::assertSame('1000000000', $fee->gasPrice);
        self::assertSame('1500000000', $fee->maxFeePerGas);
    }

    public function testGetFeeHistoryDecodesGweiMedian(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_feeHistory')->willReturn([
            'oldestBlock'   => '0x1',
            'baseFeePerGas' => ['0x3b9aca00', '0x3b9aca00'],
            'gasUsedRatio'  => [0.5],
            'reward'        => [['0x59682f00']],
        ]);

        self::assertSame(2.5, $this->provider($client)->getFeeHistory(1, BlockTag::Latest, [50])->decodeGweiMedian());
    }

    public function testSendTransactionPrefixesWith0x(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->expects($this->once())->method('eth_sendRawTransaction')->with('0xdeadbeef')->willReturn('0xtxhash');

        self::assertSame('0xtxhash', $this->provider($client)->sendTransaction('deadbeef'));
    }

    public function testWaitForTransactionReturnsBundleOnFirstPoll(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_getTransactionReceipt')->willReturn(['status' => '0x1', 'blockNumber' => '0x10']);
        $client->method('eth_blockNumber')->willReturn(16);
        $client->method('eth_getTransactionByHash')->willReturn(['from' => '0xabc', 'value' => '0x0']);

        $bundle = $this->provider($client)->waitForTransaction('0xhash', 1);
        if (!$bundle instanceof EthereumTxBundle) {
            self::fail('expected a settled bundle');
        }
        self::assertTrue($bundle->isStatusSuccess());
    }

    public function testWaitForTransactionTimesOutImmediately(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);

        self::assertNull($this->provider($client)->waitForTransaction('0xhash', 1, 0));
    }

    public function testWaitForTransactionThrowsOnMissingBlockNumber(): void
    {
        $client = $this->createMock(EthRpcClientInterface::class);
        $client->method('eth_getTransactionReceipt')->willReturn(['status' => '0x1']);

        $this->expectException(RuntimeException::class);
        $this->provider($client)->waitForTransaction('0xhash', 1);
    }

    public function testFormatEtherAndUnits(): void
    {
        self::assertSame('1', JsonRpcProvider::formatEther('1000000000000000000'));
        self::assertSame('1.5', JsonRpcProvider::formatEther('1500000000000000000'));
        self::assertSame('12.5', JsonRpcProvider::formatUnits('12500000', 6));
        self::assertSame('0', JsonRpcProvider::formatEther('0'));
    }

    private function provider(EthRpcClientInterface $client): JsonRpcProvider
    {
        return new JsonRpcProvider($client, new FrozenClock());
    }
}
