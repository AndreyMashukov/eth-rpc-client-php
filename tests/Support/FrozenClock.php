<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Support;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final readonly class FrozenClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct(string $at = '2026-01-01T00:00:00Z')
    {
        $this->now = new DateTimeImmutable($at);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
