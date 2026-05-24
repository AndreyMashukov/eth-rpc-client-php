<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Vo;

final readonly class EthereumFeeData
{
    /**
     * @param null|numeric-string $gasPrice             decimal wei — legacy / type-0 only, null on EIP-1559
     * @param numeric-string      $maxFeePerGas         decimal wei — ethers.js formula: 2 * baseFee + tip
     * @param numeric-string      $maxPriorityFeePerGas decimal wei — miner tip
     */
    public function __construct(
        public ?string $gasPrice,
        public string $maxFeePerGas,
        public string $maxPriorityFeePerGas,
    ) {}
}
