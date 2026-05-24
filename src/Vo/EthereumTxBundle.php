<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Vo;

final readonly class EthereumTxBundle
{
    public function __construct(
        public EthereumTransaction $transaction,
        public EthereumTransactionReceipt $receipt,
    ) {}

    public function isStatusSuccess(): bool
    {
        return $this->receipt->isStatusSuccess();
    }

    public function isStatusFail(): bool
    {
        return $this->receipt->isStatusFail();
    }

    public function isStatusPending(): bool
    {
        return $this->receipt->isStatusPending();
    }
}
