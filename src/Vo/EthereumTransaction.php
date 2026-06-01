<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Vo;

use Amashukov\EthRpc\Numeric\HexBig;
use Amashukov\EthRpc\Numeric\HexInt;

final readonly class EthereumTransaction
{
    public function __construct(
        public string $hash,
        public string $from,
        public ?string $to,
        public string $value,
        public ?int $nonce,
        public ?string $gasLimit,
        public ?string $gasPrice,
        public ?string $maxFeePerGas,
        public ?string $maxPriorityFeePerGas,
        public ?string $chainId,
        public ?string $data,
        public EthereumTransactionType $type,
        public bool $present = true,
    ) {}

    public function isPresent(): bool
    {
        return $this->present;
    }

    /**
     * @param null|array<string, mixed> $tx eth_getTransactionByHash result envelope
     */
    public static function fromArray(string $hash, ?array $tx): self
    {
        if (null === $tx) {
            return new self(
                hash: $hash,
                from: '',
                to: null,
                value: '0',
                nonce: null,
                gasLimit: null,
                gasPrice: null,
                maxFeePerGas: null,
                maxPriorityFeePerGas: null,
                chainId: null,
                data: null,
                type: EthereumTransactionType::Legacy,
                present: false,
            );
        }

        return new self(
            hash: $hash,
            from: strtolower(Wire::nullableStr($tx['from'] ?? null) ?? ''),
            to: self::lowerOrNull(Wire::nullableStr($tx['to'] ?? null)),
            value: self::hexBigField($tx, 'value') ?? '0',
            nonce: self::intHexField($tx, 'nonce'),
            gasLimit: self::hexBigField($tx, 'gas'),
            gasPrice: self::hexBigField($tx, 'gasPrice'),
            maxFeePerGas: self::hexBigField($tx, 'maxFeePerGas'),
            maxPriorityFeePerGas: self::hexBigField($tx, 'maxPriorityFeePerGas'),
            chainId: self::hexBigField($tx, 'chainId'),
            data: Wire::nullableStr($tx['input'] ?? null),
            type: self::resolveType($tx),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function resolveType(array $row): EthereumTransactionType
    {
        $raw = self::intHexField($row, 'type');

        return null === $raw ? EthereumTransactionType::Legacy : (EthereumTransactionType::tryFrom($raw) ?? EthereumTransactionType::Legacy);
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
}
