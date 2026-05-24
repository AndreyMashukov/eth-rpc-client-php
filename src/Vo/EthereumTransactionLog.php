<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Vo;

use Amashukov\EthRpc\Numeric\HexInt;

final readonly class EthereumTransactionLog
{
    /**
     * @param list<string> $topics 0x-hex 32-byte topic words
     */
    public function __construct(
        public string $address,
        public array $topics,
        public string $data,
        public ?int $logIndex,
        public bool $removed,
        public ?string $transactionHash,
        public ?string $blockNumber,
        public ?string $blockHash,
        public ?int $transactionIndex,
    ) {}

    /**
     * @param array<array-key, mixed> $row raw eth_getLogs result entry
     */
    public static function fromArray(array $row): self
    {
        $topicsRaw = $row['topics'] ?? [];
        $topics    = [];
        if (is_array($topicsRaw)) {
            foreach ($topicsRaw as $t) {
                $topics[] = Wire::str($t);
            }
        }

        $logIndexHex = $row['logIndex'] ?? null;
        $logIndex    = is_string($logIndexHex) && '' !== $logIndexHex ? HexInt::fromHex($logIndexHex) : null;

        $txIndexHex = $row['transactionIndex'] ?? null;
        $txIndex    = is_string($txIndexHex) && '' !== $txIndexHex ? HexInt::fromHex($txIndexHex) : null;

        $txHash    = $row['transactionHash'] ?? null;
        $blockNum  = $row['blockNumber']     ?? null;
        $blockHash = $row['blockHash']       ?? null;

        return new self(
            address: strtolower(Wire::str($row['address'] ?? null)),
            topics: $topics,
            data: Wire::str($row['data'] ?? null, '0x'),
            logIndex: $logIndex,
            removed: (bool) ($row['removed'] ?? false),
            transactionHash: is_string($txHash) ? $txHash : null,
            blockNumber: is_string($blockNum) ? $blockNum : null,
            blockHash: is_string($blockHash) ? $blockHash : null,
            transactionIndex: $txIndex,
        );
    }
}
