<?php

declare(strict_types=1);

namespace Amashukov\EthRpc\Tests\Support;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

final class StubClientException extends RuntimeException implements ClientExceptionInterface {}
