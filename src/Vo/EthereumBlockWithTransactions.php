<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Vo;

use UnexpectedValueException;

final readonly class EthereumBlockWithTransactions
{
    /**
     * @param list<EthereumTransaction> $transactions
     */
    public function __construct(
        public EthereumBlock $block,
        public array $transactions,
    ) {}

    /**
     * @param null|array<string, mixed> $row eth_getBlockByNumber result envelope requested with full transaction objects
     */
    public static function fromArray(?array $row): ?self
    {
        $block = EthereumBlock::fromArray($row);
        if (!$block instanceof EthereumBlock || null === $row) {
            return null;
        }

        $rows = $row['transactions'] ?? [];
        if (!is_array($rows)) {
            throw new UnexpectedValueException('A block carried a non-list transactions field.');
        }

        $transactions = [];
        foreach ($rows as $tx) {
            if (!is_array($tx)) {
                throw new UnexpectedValueException('A block requested with full transactions carried a transaction hash instead of an object.');
            }
            $fields = [];
            foreach ($tx as $key => $value) {
                $fields[(string) $key] = $value;
            }
            $transactions[] = EthereumTransaction::fromArray(Wire::str($fields['hash'] ?? null), $fields);
        }

        return new self($block, $transactions);
    }
}
