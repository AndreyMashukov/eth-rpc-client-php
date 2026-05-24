<?php

declare(strict_types=1);

namespace Amashukov\EthRpc;

interface EthRpcClientInterface
{
    /**
     * Returns the current block number as a decimal integer.
     */
    public function eth_blockNumber(): int;

    /**
     * Returns a block object by block number or tag, or null when not found.
     *
     * @internal Consumed by `JsonRpcProvider::getBlock` / `getFeeData` to
     *           assemble a typed `EthereumBlock` VO.
     *
     * @param string $blockHashOrTag hex block number (e.g. "0x10d4f") or tag ("latest", "earliest", "pending")
     *
     * @return null|array<string, mixed>
     */
    public function eth_getBlockByNumber(string $blockHashOrTag): ?array;

    /**
     * Returns an array of log objects matching the given filter.
     *
     * @internal Consumed by `JsonRpcProvider::getTypedLogs`.
     *
     * @param array<string, mixed> $filter e.g. ["fromBlock", "toBlock", "address", "topics"]
     *
     * @return array<int, array<string, mixed>>
     */
    public function eth_getLogs(array $filter): array;

    /**
     * Returns a transaction object by hash, or null when not found.
     *
     * @internal Consumed by `JsonRpcProvider::getTypedTransaction` / `waitForTransaction`.
     *
     * @return null|array<string, mixed>
     */
    public function eth_getTransactionByHash(string $hash): ?array;

    /**
     * Returns a transaction receipt by hash, or null when not yet mined.
     *
     * @internal Consumed by `JsonRpcProvider::getTypedTransaction` / `waitForTransaction`.
     *
     * @return null|array<string, mixed>
     */
    public function eth_getTransactionReceipt(string $hash): ?array;

    /**
     * Returns the balance of the given address in wei as a hex string.
     *
     * @param string $address  0x-prefixed account address
     * @param string $blockTag block tag ("latest", "earliest", "pending") or hex block number
     *
     * @return string hex-encoded wei value (e.g. "0x0DE0B6B3A7640000")
     */
    public function eth_getBalance(string $address, string $blockTag = 'latest'): string;

    /**
     * Executes a new message call immediately without creating a transaction.
     *
     * @param array<string, mixed> $tx       transaction object (to, data, from, gas, value, …)
     * @param string               $blockTag block tag or hex block number
     *
     * @return string hex-encoded return data
     */
    public function eth_call(array $tx, string $blockTag = 'latest'): string;

    /**
     * Returns the chain ID as a decimal integer.
     */
    public function eth_chainId(): int;

    /**
     * Returns the contract bytecode at the given address as a hex string.
     * Returns "0x" for addresses without code (EOAs).
     */
    public function eth_getCode(string $address, string $blockTag = 'latest'): string;

    /**
     * Returns the transaction count (nonce) at the given address as a decimal int.
     */
    public function eth_getTransactionCount(string $address, string $blockTag = 'latest'): int;

    /**
     * Returns the current gas price in wei as a hex string.
     */
    public function eth_gasPrice(): string;

    /**
     * Returns a suggested miner-tip (priority fee) in wei as a hex string.
     */
    public function eth_maxPriorityFeePerGas(): string;

    /**
     * Submits a signed raw transaction to the network. Returns the tx hash.
     */
    public function eth_sendRawTransaction(string $rawHex): string;

    /**
     * Returns historical base-fee + gas-used + per-percentile priority-tip
     * envelope for the trailing `$blockCount` blocks.
     *
     * @internal Consumed by `JsonRpcProvider::getFeeHistory`.
     *
     * @see https://github.com/ethereum/execution-apis (eth_feeHistory)
     *
     * @param int             $blockCount        number of blocks in the lookback window
     * @param BlockTag|string $newest            latest block in the window — typed `BlockTag`, a literal tag, or a hex block number
     * @param list<float|int> $rewardPercentiles ascending priority-fee percentiles to sample (1–100)
     *
     * @return array{oldestBlock: string, baseFeePerGas: list<string>, gasUsedRatio: list<float>, reward: list<list<string>>}
     */
    public function eth_feeHistory(int $blockCount, BlockTag|string $newest, array $rewardPercentiles): array;
}
