<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Vo;

enum EthereumTransactionType: int
{
    case Legacy  = 0;
    case Eip2930 = 1;
    case Eip1559 = 2;
    case Eip4844 = 3;
    case Eip7702 = 4;
}
