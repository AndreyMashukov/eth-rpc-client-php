<?php

declare(strict_types=1);

namespace Amashukov\EthRpc;

use RuntimeException;
use Throwable;

final class TransactionNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $hash,
        ?Throwable $previous = null,
    ) {
        parent::__construct(sprintf('Transaction %s not found on-chain (no eth_getTransactionByHash result)', $hash), 0, $previous);
    }
}
