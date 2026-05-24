<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Vo;

use Amashukov\EthRpc\Numeric\HexBig;
use Amashukov\EthRpc\Numeric\HexInt;

final readonly class EthereumTransactionReceipt
{
    /**
     * @param list<EthereumTransactionLog> $logs
     */
    public function __construct(
        public string $hash,
        public EthereumTransactionStatus $status,
        public ?string $revertReason,
        public ?string $blockNumber,
        public ?string $blockHash,
        public ?int $transactionIndex,
        public ?string $from,
        public ?string $to,
        public ?string $contractAddress,
        public ?string $gasUsed,
        public ?string $cumulativeGasUsed,
        public ?string $effectiveGasPrice,
        public ?string $fee,
        public EthereumTransactionType $type,
        public ?string $logsBloom,
        public ?string $root,
        public ?string $blobGasUsed,
        public ?string $blobGasPrice,
        public array $logs,
    ) {}

    public function isStatusSuccess(): bool
    {
        return EthereumTransactionStatus::Success === $this->status;
    }

    public function isStatusFail(): bool
    {
        return EthereumTransactionStatus::Failure === $this->status;
    }

    public function isStatusPending(): bool
    {
        return EthereumTransactionStatus::Pending === $this->status;
    }

    /**
     * @param null|array<string, mixed> $receipt eth_getTransactionReceipt result envelope
     */
    public static function fromArray(string $hash, ?array $receipt, ?string $revertReason = null): self
    {
        if (null === $receipt) {
            return new self(
                hash: $hash,
                status: EthereumTransactionStatus::Pending,
                revertReason: null,
                blockNumber: null,
                blockHash: null,
                transactionIndex: null,
                from: null,
                to: null,
                contractAddress: null,
                gasUsed: null,
                cumulativeGasUsed: null,
                effectiveGasPrice: null,
                fee: null,
                type: EthereumTransactionType::Legacy,
                logsBloom: null,
                root: null,
                blobGasUsed: null,
                blobGasPrice: null,
                logs: [],
            );
        }

        $statusRaw = strtolower(Wire::str($receipt['status'] ?? null));
        $status    = match ($statusRaw) {
            '0x1', '1' => EthereumTransactionStatus::Success,
            '0x0', '0' => EthereumTransactionStatus::Failure,
            default    => EthereumTransactionStatus::Failure,
        };

        $gasUsed           = self::hexBigField($receipt, 'gasUsed');
        $effectiveGasPrice = self::hexBigField($receipt, 'effectiveGasPrice');
        $fee               = (null !== $gasUsed && null !== $effectiveGasPrice)
            ? gmp_strval(gmp_mul($gasUsed, $effectiveGasPrice))
            : null;

        $logs = [];
        foreach ((array) ($receipt['logs'] ?? []) as $logRow) {
            if (is_array($logRow)) {
                $logs[] = EthereumTransactionLog::fromArray($logRow);
            }
        }

        return new self(
            hash: $hash,
            status: $status,
            revertReason: $revertReason,
            blockNumber: self::hexBigField($receipt, 'blockNumber'),
            blockHash: Wire::nullableStr($receipt['blockHash'] ?? null),
            transactionIndex: self::intHexField($receipt, 'transactionIndex'),
            from: self::lowerOrNull(Wire::nullableStr($receipt['from'] ?? null)),
            to: self::lowerOrNull(Wire::nullableStr($receipt['to'] ?? null)),
            contractAddress: self::lowerOrNull(Wire::nullableStr($receipt['contractAddress'] ?? null)),
            gasUsed: $gasUsed,
            cumulativeGasUsed: self::hexBigField($receipt, 'cumulativeGasUsed'),
            effectiveGasPrice: $effectiveGasPrice,
            fee: $fee,
            type: self::resolveType($receipt),
            logsBloom: Wire::nullableStr($receipt['logsBloom'] ?? null),
            root: Wire::nullableStr($receipt['root'] ?? null),
            blobGasUsed: self::hexBigField($receipt, 'blobGasUsed'),
            blobGasPrice: self::hexBigField($receipt, 'blobGasPrice'),
            logs: $logs,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function intHexField(array $row, string $key): ?int
    {
        $value = Wire::nullableStr($row[$key] ?? null);
        if (null === $value || '' === $value) {
            return null;
        }

        return HexInt::fromHex($value);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return null|numeric-string
     */
    private static function hexBigField(array $row, string $key): ?string
    {
        $value = Wire::nullableStr($row[$key] ?? null);
        if (null === $value || '' === $value) {
            return null;
        }

        return HexBig::fromHex($value);
    }

    private static function lowerOrNull(?string $value): ?string
    {
        return null === $value ? null : strtolower($value);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function resolveType(array $row): EthereumTransactionType
    {
        $raw = self::intHexField($row, 'type');

        return null === $raw ? EthereumTransactionType::Legacy : (EthereumTransactionType::tryFrom($raw) ?? EthereumTransactionType::Legacy);
    }
}
