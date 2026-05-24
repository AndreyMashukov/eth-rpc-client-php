<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Vo;

use Amashukov\EthRpc\Numeric\HexBig;
use Amashukov\EthRpc\Numeric\HexInt;

final readonly class EthereumBlock
{
    /**
     * @param null|numeric-string $number        decimal block number; null for pending block
     * @param null|numeric-string $baseFeePerGas decimal wei; null on pre-1559 / pre-genesis
     * @param null|numeric-string $gasUsed       decimal
     * @param null|numeric-string $gasLimit      decimal
     */
    public function __construct(
        public ?string $number,
        public string $hash,
        public string $parentHash,
        public int $timestamp,
        public ?string $baseFeePerGas,
        public ?string $gasUsed,
        public ?string $gasLimit,
    ) {}

    /**
     * @param null|array<string, mixed> $row eth_getBlockByNumber result envelope
     */
    public static function fromArray(?array $row): ?self
    {
        if (null === $row) {
            return null;
        }

        $numberHex = $row['number'] ?? null;
        $number    = is_string($numberHex) && '' !== $numberHex ? HexBig::fromHex($numberHex) : null;

        $baseFeeHex    = $row['baseFeePerGas'] ?? null;
        $baseFeePerGas = is_string($baseFeeHex) && '' !== $baseFeeHex ? HexBig::fromHex($baseFeeHex) : null;

        $gasUsedHex = $row['gasUsed'] ?? null;
        $gasUsed    = is_string($gasUsedHex) && '' !== $gasUsedHex ? HexBig::fromHex($gasUsedHex) : null;

        $gasLimitHex = $row['gasLimit'] ?? null;
        $gasLimit    = is_string($gasLimitHex) && '' !== $gasLimitHex ? HexBig::fromHex($gasLimitHex) : null;

        $timestampHex = $row['timestamp'] ?? '0x0';
        $timestamp    = is_string($timestampHex) && '' !== $timestampHex ? HexInt::fromHex($timestampHex) : 0;

        return new self(
            number: $number,
            hash: Wire::str($row['hash'] ?? null),
            parentHash: Wire::str($row['parentHash'] ?? null),
            timestamp: $timestamp,
            baseFeePerGas: $baseFeePerGas,
            gasUsed: $gasUsed,
            gasLimit: $gasLimit,
        );
    }
}
