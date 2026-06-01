<?php

declare(strict_types=1);

namespace Amashukov\EthRpc;

use Amashukov\EthRpc\Vo\EthereumBlock;
use Amashukov\EthRpc\Vo\EthereumFeeData;
use Amashukov\EthRpc\Vo\EthereumFeeHistory;
use Amashukov\EthRpc\Vo\EthereumTransaction;
use Amashukov\EthRpc\Vo\EthereumTransactionLog;
use Amashukov\EthRpc\Vo\EthereumTransactionReceipt;
use Amashukov\EthRpc\Vo\EthereumTxBundle;

interface JsonRpcProviderInterface
{
    /**
     * Returns the latest block number as a decimal int.
     */
    public function getBlockNumber(): int;

    /**
     * Returns the wei balance of the given address as a decimal string.
     * String (NOT int) because ETH balances approach PHP_INT_MAX past ~9 ETH.
     */
    public function getBalance(string $address, string $blockTag = 'latest'): string;

    /**
     * Returns the contract bytecode at $address. "0x" for EOAs.
     */
    public function getCode(string $address, string $blockTag = 'latest'): string;

    /**
     * Returns the transaction count (nonce) at $address.
     */
    public function getTransactionCount(string $address, string $blockTag = 'latest'): int;

    /**
     * Returns the current gas price in wei as a decimal string.
     */
    public function getGasPrice(): string;

    /**
     * Returns the chain ID as a decimal int.
     */
    public function getChainId(): int;

    /**
     * Typed `EthereumBlock` VO; null when the block is missing.
     */
    public function getBlock(string $numberOrTag): ?EthereumBlock;

    /**
     * Typed VO pair (request envelope + execution receipt) for $hash,
     * combining `eth_getTransactionByHash` + `eth_getTransactionReceipt`.
     * EIP-658 status: `0x1` → Success, `0x0` → Failure; null receipt → Pending.
     *
     * @throws TransactionNotFoundException when `eth_getTransactionByHash` has no result for $hash
     */
    public function getTypedTransaction(string $hash): EthereumTxBundle;

    /**
     * Typed `eth_getTransactionByHash` result. The returned VO carries
     * `isPresent() === false` when the node has no transaction for $hash
     * (neither mined nor in the mempool).
     */
    public function getTransactionByHashTyped(string $hash): EthereumTransaction;

    /**
     * Typed `eth_getTransactionReceipt` result. A null receipt (tx not yet
     * mined) maps to an `EthereumTransactionReceipt` with `Pending` status.
     */
    public function getTransactionReceiptTyped(string $hash): EthereumTransactionReceipt;

    /**
     * Typed log list wrapping `eth_getLogs`.
     *
     * @param array<string, mixed> $filter eth_getLogs filter shape (`address`, `topics`, `fromBlock`, `toBlock`)
     *
     * @return list<EthereumTransactionLog>
     */
    public function getTypedLogs(array $filter): array;

    /**
     * Executes a view call (eth_call) and returns hex result.
     *
     * @param array<string, mixed> $tx
     */
    public function call(array $tx, string $blockTag = 'latest'): string;

    /**
     * Network metadata: ['chainId' => int, 'name' => string].
     *
     * @param array<int, string> $nameByChainId optional id-to-name map (e.g. [1 => 'mainnet', 11155111 => 'sepolia'])
     *
     * @return array{chainId: int, name: string}
     */
    public function getNetwork(array $nameByChainId = []): array;

    /**
     * Convenience: ERC-20 `balanceOf(account)` as a decimal string.
     */
    public function getErc20Balance(string $tokenAddress, string $accountAddress, string $blockTag = 'latest'): string;

    /**
     * Typed `EthereumFeeData` VO. `gasPrice` is null on EIP-1559 chains;
     * `maxFeePerGas` uses the ethers.js formula `2 × latest.baseFeePerGas + tip`.
     */
    public function getFeeData(): EthereumFeeData;

    /**
     * Typed `EthereumFeeHistory` VO wrapping `eth_feeHistory`.
     *
     * @param int             $blockCount        number of blocks in the lookback window
     * @param BlockTag|string $newest            latest block — typed `BlockTag`, a literal tag, or a hex block number
     * @param list<float|int> $rewardPercentiles ascending priority-fee percentiles to sample (1–100)
     */
    public function getFeeHistory(int $blockCount, BlockTag|string $newest, array $rewardPercentiles): EthereumFeeHistory;

    /**
     * Broadcast a signed raw transaction (0x-prefixed hex); returns the tx hash.
     */
    public function sendTransaction(string $rawHex): string;

    /**
     * Poll until the receipt has at least $confirmations blocks on top.
     * Returns the typed VO pair, or `null` if the deadline elapses first.
     */
    public function waitForTransaction(string $txHash, int $confirmations = 1, int $timeoutMs = 600_000): ?EthereumTxBundle;
}
