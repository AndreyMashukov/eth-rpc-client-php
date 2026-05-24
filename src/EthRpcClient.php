<?php

declare(strict_types=1);

namespace Amashukov\EthRpc;

use Amashukov\EthRpc\Numeric\HexInt;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class EthRpcClient implements EthRpcClientInterface
{
    private int $idCounter = 0;

    public function __construct(
        private readonly ClientInterface $http,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly string $rpcUrl,
    ) {}

    public function eth_blockNumber(): int
    {
        return (int) hexdec($this->scalarString($this->call('eth_blockNumber', [])));
    }

    public function eth_getBlockByNumber(string $blockHashOrTag): ?array
    {
        return $this->nullableObject($this->call('eth_getBlockByNumber', [$blockHashOrTag, false]));
    }

    public function eth_getLogs(array $filter): array
    {
        $result = $this->call('eth_getLogs', [$filter]);
        $rows   = [];
        foreach (is_array($result) ? $result : [] as $entry) {
            if (is_array($entry)) {
                $rows[] = $this->stringKeyed($entry);
            }
        }

        return $rows;
    }

    public function eth_getTransactionByHash(string $hash): ?array
    {
        return $this->nullableObject($this->call('eth_getTransactionByHash', [$hash]));
    }

    public function eth_getTransactionReceipt(string $hash): ?array
    {
        return $this->nullableObject($this->call('eth_getTransactionReceipt', [$hash]));
    }

    public function eth_getBalance(string $address, string $blockTag = 'latest'): string
    {
        return $this->scalarString($this->call('eth_getBalance', [$address, $blockTag]));
    }

    public function eth_call(array $tx, string $blockTag = 'latest'): string
    {
        return $this->scalarString($this->call('eth_call', [$tx, $blockTag]));
    }

    public function eth_chainId(): int
    {
        return (int) hexdec($this->scalarString($this->call('eth_chainId', [])));
    }

    public function eth_getCode(string $address, string $blockTag = 'latest'): string
    {
        return $this->scalarString($this->call('eth_getCode', [$address, $blockTag]));
    }

    public function eth_getTransactionCount(string $address, string $blockTag = 'latest'): int
    {
        return (int) hexdec($this->scalarString($this->call('eth_getTransactionCount', [$address, $blockTag])));
    }

    public function eth_gasPrice(): string
    {
        return $this->scalarString($this->call('eth_gasPrice', []));
    }

    public function eth_maxPriorityFeePerGas(): string
    {
        return $this->scalarString($this->call('eth_maxPriorityFeePerGas', []));
    }

    public function eth_sendRawTransaction(string $rawHex): string
    {
        return $this->scalarString($this->call('eth_sendRawTransaction', [$rawHex]));
    }

    public function eth_feeHistory(int $blockCount, BlockTag|string $newest, array $rewardPercentiles): array
    {
        $tag = $newest instanceof BlockTag ? $newest->value : $newest;
        $raw = $this->call('eth_feeHistory', [HexInt::toHex($blockCount), $tag, $rewardPercentiles]);
        $raw = is_array($raw) ? $raw : [];

        $baseFeePerGas = [];
        foreach (is_array($raw['baseFeePerGas'] ?? null) ? $raw['baseFeePerGas'] : [] as $v) {
            $baseFeePerGas[] = $this->scalarString($v);
        }

        $gasUsedRatio = [];
        foreach (is_array($raw['gasUsedRatio'] ?? null) ? $raw['gasUsedRatio'] : [] as $v) {
            $gasUsedRatio[] = is_numeric($v) ? (float) $v : 0.0;
        }

        $reward = [];
        foreach (is_array($raw['reward'] ?? null) ? $raw['reward'] : [] as $blockRow) {
            $row = [];
            foreach (is_array($blockRow) ? $blockRow : [] as $v) {
                $row[] = $this->scalarString($v);
            }
            $reward[] = $row;
        }

        return [
            'oldestBlock'   => $this->scalarString($raw['oldestBlock'] ?? null, '0x0'),
            'baseFeePerGas' => $baseFeePerGas,
            'gasUsedRatio'  => $gasUsedRatio,
            'reward'        => $reward,
        ];
    }

    /**
     * @param list<mixed> $params
     *
     * @throws EthRpcException on JSON-RPC error or malformed response
     */
    private function call(string $method, array $params): mixed
    {
        $id      = ++$this->idCounter;
        $payload = [
            'jsonrpc' => '2.0',
            'method'  => $method,
            'params'  => $params,
            'id'      => $id,
        ];

        try {
            $body = json_encode($payload, \JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new EthRpcException(sprintf('Failed to encode request for %s', $method), 0, $exception);
        }

        $request = $this->requestFactory
            ->createRequest('POST', $this->rpcUrl)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($body));

        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $exception) {
            throw new EthRpcException(sprintf('HTTP transport error calling %s: %s', $method, $exception->getMessage()), 0, $exception);
        }

        try {
            $json = json_decode((string) $response->getBody(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new EthRpcException(sprintf('Invalid JSON response from %s', $method), 0, $exception);
        }

        if (!is_array($json)) {
            throw new EthRpcException(sprintf('Invalid ETH RPC response from %s: not a JSON object', $method));
        }

        if (isset($json['error']) && is_array($json['error'])) {
            $err     = $json['error'];
            $codeRaw = $err['code'] ?? null;
            $code    = is_numeric($codeRaw) ? (int) $codeRaw : 0;
            $message = $this->scalarString($err['message'] ?? null, 'unknown error');

            throw new EthRpcException(sprintf('ETH RPC error from %s: [%d] %s', $method, $code, $message), $code);
        }

        if (!\array_key_exists('result', $json)) {
            throw new EthRpcException(sprintf('Invalid ETH RPC response from %s: missing "result" field', $method));
        }

        return $json['result'];
    }

    private function scalarString(mixed $value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * @return null|array<string, mixed>
     */
    private function nullableObject(mixed $value): ?array
    {
        if (null === $value) {
            return null;
        }
        if (!is_array($value)) {
            throw new EthRpcException('Invalid ETH RPC response: expected object or null');
        }

        return $this->stringKeyed($value);
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<string, mixed>
     */
    private function stringKeyed(array $value): array
    {
        $out = [];
        foreach ($value as $k => $v) {
            $out[(string) $k] = $v;
        }

        return $out;
    }
}
