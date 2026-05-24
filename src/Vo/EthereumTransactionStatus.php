<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Vo;

enum EthereumTransactionStatus: int
{
    case Pending = -1;
    case Failure = 0;
    case Success = 1;
}
