# amashukov/eth-rpc-client-php

Ethereum / EVM JSON-RPC client in pure PHP — raw `eth_*` mirror plus an ethers.js v6-style typed provider over any PSR-18 client, with EIP-1559 fee data.

[![CI](https://img.shields.io/github/actions/workflow/status/AndreyMashukov/eth-rpc-client-php/ci.yml?branch=main&label=CI)](https://github.com/AndreyMashukov/eth-rpc-client-php/actions)
[![PHPStan L9](https://img.shields.io/github/actions/workflow/status/AndreyMashukov/eth-rpc-client-php/stan.yml?branch=main&label=PHPStan%20L9)](https://github.com/AndreyMashukov/eth-rpc-client-php/actions)
[![Latest Version](https://img.shields.io/packagist/v/amashukov/eth-rpc-client-php)](https://packagist.org/packages/amashukov/eth-rpc-client-php)
[![Downloads](https://img.shields.io/packagist/dt/amashukov/eth-rpc-client-php)](https://packagist.org/packages/amashukov/eth-rpc-client-php)
[![PHP](https://img.shields.io/packagist/dependency-v/amashukov/eth-rpc-client-php/php)](https://packagist.org/packages/amashukov/eth-rpc-client-php)
[![License](https://img.shields.io/packagist/l/amashukov/eth-rpc-client-php)](LICENSE)
[![Stars](https://img.shields.io/github/stars/AndreyMashukov/eth-rpc-client-php?style=social)](https://github.com/AndreyMashukov/eth-rpc-client-php)

An **Ethereum JSON-RPC client in pure PHP** for any EVM chain, built on any [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client. Two layers: a raw `eth_*` mirror (`EthRpcClient`) for audit-grade hex parity with the wire, and an [ethers.js](https://docs.ethers.org/v6/) v6-flavoured facade (`JsonRpcProvider`) returning typed Value Objects — `EthereumTransaction`, `EthereumTxBundle`, `EthereumBlock`, `EthereumFeeData`, `EthereumFeeHistory`, `EthereumTransactionLog`. Wei values that exceed `PHP_INT_MAX` stay safe as decimal strings via `ext-gmp`, never lossy floats. EIP-1559 fee composition follows ethers.js (`maxFeePerGas = 2 × baseFee + tip`), and the provider gracefully handles real-world node quirks like Erigon's bare `0x` returns and missing `baseFeePerGas`.

## Features

- **Two layers, one transport** — `EthRpcClient` mirrors the `eth_*` namespace (`eth_blockNumber`, `eth_getBalance`, `eth_call`, `eth_getTransactionByHash`, `eth_getTransactionReceipt`, `eth_getLogs`, `eth_feeHistory`, …); `JsonRpcProvider` wraps it with typed Value Objects and ethers.js naming.
- **Transport-agnostic** — bring your own PSR-18 client + PSR-17 factories. Retry / key-rotation / load-balancing are middleware concerns, not baked in.
- **Typed transaction surface** — `getTypedTransaction()` / `waitForTransaction()` return an `EthereumTxBundle` with EIP-658 `isStatusSuccess()` / `isStatusFail()` / `isStatusPending()` predicates, so call sites never compare raw `'0x1'` / `'0x0'` strings.
- **EIP-1559 fee data** — `getFeeData()` mirrors ethers.js (`maxFeePerGas = 2 × baseFee + tip`); `getFeeHistory()` carries the gwei-median math.
- **Node-quirk tolerant** — handles Erigon's bare `'0x'` empty returns and pre-London / missing-`baseFeePerGas` blocks without throwing.
- **Bigint-safe numeric helpers** — `Numeric\HexInt` / `HexBig` / `Wei`.
- **PSR-20 clock injection** — `waitForTransaction` deadlines are driven by an injected `Psr\Clock\ClockInterface`, so polling is fully testable.

## Why amashukov/eth-rpc-client-php

`web3p/web3.php` is effectively abandoned and predates EIP-1559 — no typed fee data, no London-fork fee composition. This package targets modern PHP 8.3, ships EIP-1559 fee math out of the box, returns typed Value Objects with bigint-safe decimal strings, keeps PHPStan level 9 across the surface, and stays transport-agnostic so retries and API keys live in your PSR-18 pipeline.

## Installation

```bash
composer require amashukov/eth-rpc-client-php
```

## Usage

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

$fee  = $provider->getFeeData();                           // EthereumFeeData
$usdt = $provider->getErc20Balance('0xdAC17...', '0xholder...');
```

Wire a retry / API-key pipeline by composing
[`amashukov/http-client-php`](https://github.com/AndreyMashukov/http-client-php)
middlewares around the `CurlClient` and passing the resulting PSR-18
`Pipeline` into `EthRpcClient`.

### Two layers

| Layer | Use it for |
|-------|-----------|
| `EthRpcClient` (`eth_*`) | Audit-grade paths that need hex parity with the wire format. |
| `JsonRpcProvider` | New code — typed VOs, decimal-string balances, ethers.js naming. |

### Signing

This package broadcasts already-signed raw transactions via
`sendTransaction()`. Offline EIP-1559 / EIP-155 signing lives in the
companion [`amashukov/eip1559-tx-signer-php`](https://github.com/AndreyMashukov/eip1559-tx-signer-php).

## Requirements

- PHP 8.3+
- `ext-gmp`, `ext-bcmath`
- A PSR-18 client + PSR-17 request / stream factories + a PSR-20 clock

## Related packages

| Package | Role |
|---------|------|
| [amashukov/abi-encoder-php](https://github.com/AndreyMashukov/abi-encoder-php) | Solidity ABI encode / decode (dependency) |
| [amashukov/eip1559-tx-signer-php](https://github.com/AndreyMashukov/eip1559-tx-signer-php) | Offline EIP-1559 / EIP-155 transaction signing |
| [amashukov/http-client-php](https://github.com/AndreyMashukov/http-client-php) | PSR-18 cURL client + retry / header-injection middleware |
| [amashukov/keccak-php](https://github.com/AndreyMashukov/keccak-php) | Keccak-256 hashing |
| [amashukov/eth-php](https://github.com/AndreyMashukov/eth-php) | Ethereum meta-package |
| [amashukov/blockchain-context-bundle](https://github.com/AndreyMashukov/blockchain-context-bundle) | Symfony bundle wiring the whole family |

## Quality

- **PHPStan level 9**, clean.
- **php-cs-fixer** `@PER-CS` ruleset.
- **GitHub Actions CI** on every push.
- Fee-composition parity checked against ethers.js v6 (`maxFeePerGas = 2 × baseFee + tip`).

## License

MIT — see [LICENSE](LICENSE).
