# eth-rpc-client-php

PHP client for the Ethereum JSON-RPC interface, with two layers:

- **`EthRpcClient`** — a low-level mirror of the `eth_*` method namespace (`eth_blockNumber`, `eth_getBalance`, `eth_call`, `eth_getTransactionByHash`, `eth_feeHistory`, …). Hex in, hex out where the wire is hex.
- **`JsonRpcProvider`** — an `ethers.js` v6-style facade returning typed value objects: `EthereumTransaction`, `EthereumTxBundle` (request + receipt), `EthereumBlock`, `EthereumFeeData`, `EthereumFeeHistory`, `EthereumTransactionLog`.

Big integers stay safe via `ext-gmp` — wei values exceeding `PHP_INT_MAX` are returned as decimal strings, not floats.

## Status

Pre-1.0. Public API may change before the 1.0 tag.

## Requirements

- PHP 8.3+
- `ext-gmp`
- `ext-curl`

## Dependencies

- [`amashukov/http-client-php`](https://github.com/AndreyMashukov/http-client-php)
- [`amashukov/abi-encoder-php`](https://github.com/AndreyMashukov/abi-encoder-php)
- [`amashukov/eip1559-tx-signer-php`](https://github.com/AndreyMashukov/eip1559-tx-signer-php)

## License

MIT License.
