<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Vo;

use Amashukov\EthRpc\Numeric\HexInt;
use RuntimeException;

final readonly class EthereumFeeHistory
{
    /**
     * @param list<string> $baseFeePerGas decimal-wei strings; one entry per block plus next block
     * @param list<float>  $gasUsedRatio  ratio per block (0.0 .. 1.0)
     * @param list<list<string>> $reward  per-block percentile-indexed priority-fee samples (decimal wei)
     */
    public function __construct(
        public string $oldestBlock,
        public array $baseFeePerGas,
        public array $gasUsedRatio,
        public array $reward,
    ) {}

    /**
     * @throws RuntimeException on missing / empty fields
     *
     * @return float gwei = (latest base fee + median priority tip) / 1e9
     */
    public function decodeGweiMedian(): float
    {
        if ([] === $this->baseFeePerGas) {
            throw new RuntimeException('eth_feeHistory: baseFeePerGas missing/empty');
        }
        if ([] === $this->reward) {
            throw new RuntimeException('eth_feeHistory: reward missing/empty');
        }

        $latestBaseFeeWei = (int) $this->baseFeePerGas[array_key_last($this->baseFeePerGas)];

        $percentileRewards = [];
        foreach ($this->reward as $blockRewards) {
            if (!isset($blockRewards[0])) {
                continue;
            }
            $percentileRewards[] = (int) $blockRewards[0];
        }

        if ([] === $percentileRewards) {
            throw new RuntimeException('eth_feeHistory: no usable reward percentiles');
        }

        sort($percentileRewards);
        $count        = \count($percentileRewards);
        $medianTipWei = 1 === $count % 2
            ? $percentileRewards[intdiv($count, 2)]
            : intdiv($percentileRewards[intdiv($count, 2) - 1] + $percentileRewards[intdiv($count, 2)], 2);

        return ($latestBaseFeeWei + $medianTipWei) / 1_000_000_000;
    }

    /**
     * @param array{oldestBlock?: string, baseFeePerGas?: list<string>, gasUsedRatio?: list<float>, reward?: list<list<string>>} $envelope
     */
    public static function fromArray(array $envelope): self
    {
        $baseFees     = $envelope['baseFeePerGas'] ?? [];
        $rewards      = $envelope['reward']        ?? [];
        $gasUsedRatio = $envelope['gasUsedRatio']  ?? [];
        $oldestBlock  = $envelope['oldestBlock']   ?? '0x0';

        $baseFeesDecimal = [];
        foreach ($baseFees as $hex) {
            $baseFeesDecimal[] = (string) HexInt::fromHex($hex);
        }

        $rewardsDecimal = [];
        foreach ($rewards as $blockRewards) {
            $rowDecimal = [];
            foreach ($blockRewards as $hex) {
                $rowDecimal[] = (string) HexInt::fromHex($hex);
            }
            $rewardsDecimal[] = $rowDecimal;
        }

        return new self(
            oldestBlock: (string) HexInt::fromHex($oldestBlock),
            baseFeePerGas: $baseFeesDecimal,
            gasUsedRatio: array_map(static fn(float $v): float => $v, $gasUsedRatio),
            reward: $rewardsDecimal,
        );
    }
}
