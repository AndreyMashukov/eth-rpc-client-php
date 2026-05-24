<?php

declare(strict_types=1);

namespace Amashukov\EthRpc;

use RuntimeException;
use Throwable;

final class EthRpcException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $rpcCode = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getRpcCode(): int
    {
        return $this->rpcCode;
    }
}
