<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests;

use Amashukov\EthRpc\BlockTag;
use Amashukov\EthRpc\EthRpcClient;
use Amashukov\EthRpc\EthRpcException;
use Amashukov\EthRpc\Tests\Support\StubClientException;
use Amashukov\EthRpc\Tests\Support\StubHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class EthRpcClientTest extends TestCase
{
    private Psr17Factory $factory;

    private const string RPC_URL = 'https://eth-node.example/rpc';

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function testBlockNumberDecodesHexAndBuildsJsonRpcEnvelope(): void
    {
        $stub   = new StubHttpClient($this->json('{"jsonrpc":"2.0","id":1,"result":"0x10d4f"}'));
        $client = $this->client($stub);

        self::assertSame(68_943, $client->eth_blockNumber());

        $request = $this->lastRequest($stub);
        self::assertSame('POST', $request->getMethod());
        self::assertSame(self::RPC_URL, (string) $request->getUri());

        $payload = json_decode((string) $request->getBody(), true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame(['jsonrpc' => '2.0', 'method' => 'eth_blockNumber', 'params' => [], 'id' => 1], $payload);
    }

    public function testGetBalanceReturnsRawHexAndSendsParams(): void
    {
        $stub   = new StubHttpClient($this->json('{"result":"0x0de0b6b3a7640000"}'));
        $client = $this->client($stub);

        self::assertSame('0x0de0b6b3a7640000', $client->eth_getBalance('0xabc', 'latest'));

        $payload = $this->decodeBody($this->lastRequest($stub));
        self::assertSame('eth_getBalance', $payload['method']);
        self::assertSame(['0xabc', 'latest'], $payload['params']);
    }

    public function testChainIdDecodesHex(): void
    {
        $client = $this->client(new StubHttpClient($this->json('{"result":"0x1"}')));

        self::assertSame(1, $client->eth_chainId());
    }

    public function testGetTransactionReceiptReturnsObject(): void
    {
        $client  = $this->client(new StubHttpClient($this->json('{"result":{"status":"0x1","blockNumber":"0x10"}}')));
        $receipt = $client->eth_getTransactionReceipt('0xhash');

        if (null === $receipt) {
            self::fail('expected a receipt object');
        }
        self::assertSame('0x1', $receipt['status']);
        self::assertSame('0x10', $receipt['blockNumber']);
    }

    public function testGetTransactionReceiptReturnsNullWhenNotMined(): void
    {
        $client = $this->client(new StubHttpClient($this->json('{"result":null}')));

        self::assertNull($client->eth_getTransactionReceipt('0xhash'));
    }

    public function testGetLogsReturnsListOfObjects(): void
    {
        $client = $this->client(new StubHttpClient($this->json('{"result":[{"address":"0xabc","data":"0x"},{"address":"0xdef","data":"0x"}]}')));

        $logs = $client->eth_getLogs(['address' => '0xabc']);

        self::assertCount(2, $logs);
        self::assertSame('0xabc', $logs[0]['address']);
        self::assertSame('0xdef', $logs[1]['address']);
    }

    public function testFeeHistoryShape(): void
    {
        $stub   = new StubHttpClient($this->json('{"result":{"oldestBlock":"0x10","baseFeePerGas":["0x1","0x2"],"gasUsedRatio":[0.5],"reward":[["0x3"]]}}'));
        $client = $this->client($stub);

        $history = $client->eth_feeHistory(2, BlockTag::Latest, [50]);

        self::assertSame('0x10', $history['oldestBlock']);
        self::assertSame(['0x1', '0x2'], $history['baseFeePerGas']);
        self::assertSame([0.5], $history['gasUsedRatio']);
        self::assertSame([['0x3']], $history['reward']);

        $payload = $this->decodeBody($this->lastRequest($stub));
        self::assertSame(['0x2', 'latest', [50]], $payload['params']);
    }

    public function testErrorEnvelopeThrowsWithRpcCode(): void
    {
        $client = $this->client(new StubHttpClient($this->json('{"error":{"code":-32000,"message":"execution reverted"}}')));

        try {
            $client->eth_call(['to' => '0xabc'], 'latest');
            self::fail('expected EthRpcException');
        } catch (EthRpcException $e) {
            self::assertSame(-32000, $e->getRpcCode());
            self::assertStringContainsString('execution reverted', $e->getMessage());
        }
    }

    public function testMissingResultThrows(): void
    {
        $client = $this->client(new StubHttpClient($this->json('{"jsonrpc":"2.0","id":1}')));

        $this->expectException(EthRpcException::class);
        $this->expectExceptionMessage('missing "result"');
        $client->eth_blockNumber();
    }

    public function testInvalidJsonThrows(): void
    {
        $client = $this->client(new StubHttpClient($this->json('not-json{')));

        $this->expectException(EthRpcException::class);
        $this->expectExceptionMessage('Invalid JSON');
        $client->eth_blockNumber();
    }

    public function testTransportExceptionIsWrapped(): void
    {
        $client = $this->client(new StubHttpClient(null, new StubClientException('connection refused')));

        $this->expectException(EthRpcException::class);
        $this->expectExceptionMessage('connection refused');
        $client->eth_blockNumber();
    }

    private function client(StubHttpClient $stub): EthRpcClient
    {
        return new EthRpcClient($stub, $this->factory, $this->factory, self::RPC_URL);
    }

    private function json(string $body): ResponseInterface
    {
        return $this->factory->createResponse(200)->withBody($this->factory->createStream($body));
    }

    private function lastRequest(StubHttpClient $stub): RequestInterface
    {
        $request = $stub->lastRequest();
        if (!$request instanceof RequestInterface) {
            self::fail('no request was issued');
        }

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBody(RequestInterface $request): array
    {
        $decoded = json_decode((string) $request->getBody(), true, 512, \JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            self::fail("request body is not a JSON object");
        }
        $out = [];
        foreach ($decoded as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
    }
}
