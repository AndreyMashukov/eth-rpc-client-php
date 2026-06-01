<?php

declare(strict_types=1);

namespace Amashukov\EthRpc;

use Amashukov\AbiEncoder\AbiEncoder;
use Amashukov\EthRpc\Numeric\HexInt;
use Amashukov\EthRpc\Vo\EthereumBlock;
use Amashukov\EthRpc\Vo\EthereumFeeData;
use Amashukov\EthRpc\Vo\EthereumFeeHistory;
use Amashukov\EthRpc\Vo\EthereumTransaction;
use Amashukov\EthRpc\Vo\EthereumTransactionLog;
use Amashukov\EthRpc\Vo\EthereumTransactionReceipt;
use Amashukov\EthRpc\Vo\EthereumTxBundle;
use Psr\Clock\ClockInterface;
use RuntimeException;

final readonly class JsonRpcProvider implements JsonRpcProviderInterface
{
    public function __construct(
        private EthRpcClientInterface $client,
        private ClockInterface $clock,
    ) {}

    public function getBlockNumber(): int
    {
        return $this->client->eth_blockNumber();
    }

    public function getBalance(string $address, string $blockTag = 'latest'): string
    {
        return $this->hexToDec($this->client->eth_getBalance($address, $blockTag));
    }

    public function getCode(string $address, string $blockTag = 'latest'): string
    {
        return $this->client->eth_getCode($address, $blockTag);
    }

    public function getTransactionCount(string $address, string $blockTag = 'latest'): int
    {
        return $this->client->eth_getTransactionCount($address, $blockTag);
    }

    public function getGasPrice(): string
    {
        return $this->hexToDec($this->client->eth_gasPrice());
    }

    public function getChainId(): int
    {
        return $this->client->eth_chainId();
    }

    public function getBlock(string $numberOrTag): ?EthereumBlock
    {
        return EthereumBlock::fromArray($this->client->eth_getBlockByNumber($numberOrTag));
    }

    public function getTypedTransaction(string $hash): EthereumTxBundle
    {
        $transaction = $this->getTransactionByHashTyped($hash);
        if (!$transaction->isPresent()) {
            throw new TransactionNotFoundException($hash);
        }

        return new EthereumTxBundle(
            transaction: $transaction,
            receipt: $this->getTransactionReceiptTyped($hash),
        );
    }

    public function getTransactionByHashTyped(string $hash): EthereumTransaction
    {
        return EthereumTransaction::fromArray($hash, $this->client->eth_getTransactionByHash($hash));
    }

    public function getTransactionReceiptTyped(string $hash): EthereumTransactionReceipt
    {
        return EthereumTransactionReceipt::fromArray($hash, $this->client->eth_getTransactionReceipt($hash));
    }

    public function getTypedLogs(array $filter): array
    {
        $logs = [];
        foreach ($this->client->eth_getLogs($filter) as $row) {
            $logs[] = EthereumTransactionLog::fromArray($row);
        }

        return $logs;
    }

    public function call(array $tx, string $blockTag = 'latest'): string
    {
        return $this->client->eth_call($tx, $blockTag);
    }

    public function getNetwork(array $nameByChainId = []): array
    {
        $id = $this->getChainId();

        return [
            'chainId' => $id,
            'name'    => $nameByChainId[$id] ?? (string) $id,
        ];
    }

    public function getErc20Balance(string $tokenAddress, string $accountAddress, string $blockTag = 'latest'): string
    {
        $data = AbiEncoder::encodeCall('balanceOf(address)', [['address', $accountAddress]]);
        $hex  = $this->client->eth_call(['to' => $tokenAddress, 'data' => $data], $blockTag);

        return $this->hexToDec($hex);
    }

    public function getFeeData(): EthereumFeeData
    {
        $tip    = $this->numericString($this->hexToDec($this->client->eth_maxPriorityFeePerGas()));
        $latest = EthereumBlock::fromArray($this->client->eth_getBlockByNumber('latest'));
        if (!$latest instanceof EthereumBlock || null === $latest->baseFeePerGas) {
            return new EthereumFeeData(
                gasPrice: $this->numericString($this->hexToDec($this->client->eth_gasPrice())),
                maxFeePerGas: $tip,
                maxPriorityFeePerGas: $tip,
            );
        }
        $baseFee = $this->numericString($latest->baseFeePerGas);
        $maxFee  = bcadd(bcmul($baseFee, '2'), $tip);

        return new EthereumFeeData(
            gasPrice: null,
            maxFeePerGas: $this->numericString($maxFee),
            maxPriorityFeePerGas: $tip,
        );
    }

    public function getFeeHistory(int $blockCount, BlockTag|string $newest, array $rewardPercentiles): EthereumFeeHistory
    {
        return EthereumFeeHistory::fromArray(
            $this->client->eth_feeHistory($blockCount, $newest, $rewardPercentiles),
        );
    }

    public function sendTransaction(string $rawHex): string
    {
        if (!str_starts_with($rawHex, '0x')) {
            $rawHex = '0x' . $rawHex;
        }

        return $this->client->eth_sendRawTransaction($rawHex);
    }

    public function waitForTransaction(string $txHash, int $confirmations = 1, int $timeoutMs = 600_000): ?EthereumTxBundle
    {
        $deadline     = (float) $this->clock->now()->format('U.u') + $timeoutMs / 1000;
        $pollInterval = 4;
        while ((float) $this->clock->now()->format('U.u') < $deadline) {
            $receiptRow = $this->client->eth_getTransactionReceipt($txHash);
            if (null !== $receiptRow) {
                $blockNumberRaw = $receiptRow['blockNumber'] ?? null;
                if (!is_string($blockNumberRaw) || '' === $blockNumberRaw) {
                    throw new RuntimeException(sprintf('eth_getTransactionReceipt(%s): missing "blockNumber" — RPC corruption', $txHash));
                }
                $minedAt = HexInt::fromHex($blockNumberRaw);
                $head    = $this->client->eth_blockNumber();
                if ($minedAt > 0 && ($head - $minedAt + 1) >= $confirmations) {
                    $txRow = $this->client->eth_getTransactionByHash($txHash);

                    return new EthereumTxBundle(
                        transaction: EthereumTransaction::fromArray($txHash, $txRow),
                        receipt: EthereumTransactionReceipt::fromArray($txHash, $receiptRow),
                    );
                }
            }
            sleep($pollInterval);
        }

        return null;
    }

    public static function formatEther(string $weiDecimal): string
    {
        return self::scaleDown($weiDecimal, 18);
    }

    public static function formatUnits(string $rawDecimal, int $decimals): string
    {
        return self::scaleDown($rawDecimal, $decimals);
    }

    /**
     * @return numeric-string
     */
    private function numericString(string $s): string
    {
        return is_numeric($s) ? $s : '0';
    }

    private function hexToDec(string $hex): string
    {
        $clean = strtolower(ltrim($hex, '0'));
        if (str_starts_with($clean, 'x')) {
            $clean = substr($clean, 1);
        }
        if ('' === $clean) {
            return '0';
        }

        return gmp_strval(gmp_init($clean, 16), 10);
    }

    private static function scaleDown(string $intStr, int $decimals): string
    {
        if ('0' === $intStr || '' === $intStr) {
            return '0';
        }
        if (0 === $decimals) {
            return $intStr;
        }

        $padded  = str_pad($intStr, $decimals + 1, '0', \STR_PAD_LEFT);
        $intPart = substr($padded, 0, -$decimals);
        $frac    = rtrim(substr($padded, -$decimals), '0');

        return '' === $frac ? $intPart : $intPart . '.' . $frac;
    }
}
