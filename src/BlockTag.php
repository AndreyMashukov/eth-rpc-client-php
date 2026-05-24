<?php

declare(strict_types=1);

namespace Amashukov\EthRpc;

enum BlockTag: string
{
    case Latest    = 'latest';
    case Finalized = 'finalized';
}
