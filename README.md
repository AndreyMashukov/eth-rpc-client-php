# eth-rpc-client-php

An Ethereum JSON-RPC client in pure PHP, built on any
[PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client. Two layers:

- **`EthRpcClient`** — a low-level mirror of the `eth_*` method namespace
  (`eth_blockNumber`, `eth_getBalance`, `eth_call`, `eth_getTransactionByHash`,
  `eth_getTransactionReceipt`, `eth_getLogs`, `eth_feeHistory`, …). Hex in,
  hex out where the wire speaks hex; decimal `int` where it doesn't.
- **`JsonRpcProvider`** — an [ethers.js](https://docs.ethers.org/v6/) v6-style
  facade returning typed Value Objects: `EthereumTxBundle` (request + receipt),
  `EthereumBlock`, `EthereumFeeData`, `EthereumFeeHistory`,
  `EthereumTransactionLog`.

Big integers stay safe via `ext-gmp` — wei values exceeding `PHP_INT_MAX`
are returned as decimal strings, never lossy floats.

## Features

- **Transport-agnostic** — bring your own PSR-18 client + PSR-17 factories.
  Retry / key-rotation / load-balancing are middleware concerns, not baked in.
- **Typed transaction surface** — `getTypedTransaction()` /
  `waitForTransaction()` return an `EthereumTxBundle` with EIP-658
  `isStatusSuccess()` / `isStatusFail()` / `isStatusPending()` predicates,
  so call sites never compare raw `'0x1'` / `'0x0'` strings.
- **EIP-1559 fee data** — `getFeeData()` mirrors ethers.js
  (`maxFeePerGas = 2 × baseFee + tip`); `getFeeHistory()` carries the
  gwei-median math.
- **Bigint-safe numeric helpers** — `Numeric\HexInt` / `HexBig` / `Wei`.
- **PSR-20 clock injection** — `waitForTransaction` deadlines are driven by an
  injected `Psr\Clock\ClockInterface`, so they're fully testable.

## Requirements

- PHP 8.3+
- `ext-gmp`, `ext-bcmath`
- A PSR-18 client + PSR-17 request/stream factories + a PSR-20 clock

## Installation

```bash
composer require amashukov/eth-rpc-client-php
```

## Quick start

```php
use Amashukov\EthRpc\EthRpcClient;
use Amashukov\EthRpc\JsonRpcProvider;
use Amashukov\HttpClient\CurlClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\Clock\NativeClock;

$psr17 = new Psr17Factory();
$http  = new CurlClient($psr17, $psr17, timeoutSeconds: 30);

$client   = new EthRpcClient($http, $psr17, $psr17, 'https://your-eth-node.example/rpc');
$provider = new JsonRpcProvider($client, new NativeClock());

$balanceWei = $provider->getBalance('0xabc...');           // decimal string
$bundle     = $provider->getTypedTransaction('0xtxhash');  // EthereumTxBundle
if ($bundle->isStatusSuccess()) {
    $gasFee = $bundle->receipt->fee;                        // decimal wei
}

$fee = $provider->getFeeData();                            // EthereumFeeData
$usdt = $provider->getErc20Balance('0xdAC17...', '0xholder...');
```

Wire a retry / API-key pipeline by composing
[`amashukov/http-client-php`](https://github.com/AndreyMashukov/http-client-php)
middlewares around the `CurlClient` and passing the resulting PSR-18
`Pipeline` into `EthRpcClient`.

## Two layers

| Layer | Use it for |
|-------|-----------|
| `EthRpcClient` (`eth_*`) | Audit-grade paths that need hex parity with the wire format. |
| `JsonRpcProvider` | New code — typed VOs, decimal-string balances, ethers.js naming. |

## Signing

This package broadcasts already-signed raw transactions via
`sendTransaction()`. Offline EIP-1559 / EIP-155 signing lives in the
companion [`amashukov/eip1559-tx-signer-php`](https://github.com/AndreyMashukov/eip1559-tx-signer-php).

## Testing

```bash
composer install
composer test     # PHPUnit
composer stan     # PHPStan (level 9)
composer cs       # php-cs-fixer (dry-run)
composer rector   # Rector (dry-run)
```

## License

MIT — see [LICENSE](LICENSE).
