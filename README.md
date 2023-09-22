
# Peak

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Github Actions][ico-gh-actions]][link-gh-actions]
[![Codecov][ico-codecov]][link-codecov]
[![Total Downloads][ico-downloads]][link-downloads]
[![Software License][ico-license]](LICENSE.md)

A simple and efficient solution for concurrently sending HTTP requests using PSR-18 client implementations.

Peak is a library that enables concurrent request sending using a request pool. It leverages the event loop of [ReactPHP](https://github.com/reactphp) or [PSL](https://github.com/azjezz/psl) to handle and manage the requests concurrently.

## Requirements
- PHP 8.1 or higher.
- A package that supports non-block I/O using Fibers under the hood (now refer as **driver**).

## Installation

You can install the package via composer:

```bash
composer require fansipan/peak
```

Additionally, depending on your choice of driver, these packages may also need to be installed.

### ReactPHP

```bash
composer require clue/mq-react react/async
```

### PSL

```bash
composer require azjezz/psl
```

## Usage

### Create Request Pool

Typical applications would use the `PoolFactory` class to create a pool.

```php
use Fansipan\Peak\PoolFactory;

/** @var \Psr\Http\Client\ClientInterface $client */
$pool = PoolFactory::createForClient($client);
```

It will attempt to create async version of the client using `AsyncClientFactory`. The supported clients are [Guzzle](https://github.com/guzzle/guzzle) and [Symfony HTTPClient](https://github.com/symfony/http-client) ([`Psr18Client`](https://symfony.com/doc/current/http_client.html#psr-18-and-psr-17)) except for [ReactPHP driver](#reactphp).

> You can use any PSR-18 client implementations with ReactPHP driver. If an unsupported client is used, it will be replaced with the [`Browser`](https://github.com/reactphp/http#browser) HTTP client.

### Sending Requests

The `send` method accepts an iterator of PSR-7 requests or closures/invokable class which receive an `Psr\Http\Client\ClientInterface` instance.

```php
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// Using array
$responses = $pool->send([
    $psr7Request,
    fn (ClientInterface $client): ResponseInterface => $client->sendRequest($psr7Request)
]);

var_dump($responses[0]);
var_dump($responses[1]);

// Using generator when you have an indeterminate amount of requests you wish to send
$requests = static function (int $total) {
    for ($i = 0; $i < $total; $i++) {
        yield $psr7Request;
    }
}
$responses = $pool->send($requests(100));
```

### Retrieving Responses

As you can see from the example above, each response instance can be accessed using an index. However, the response order is not guaranteed. If you wish, you can assign names to the requests to easily track the specific requests that have been sent. This allows you to access the corresponding responses by their assigned names.

```php
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

$responses = $pool->send([
    'first' => $psr7Request,
    'second' => fn (ClientInterface $client): ResponseInterface => $client->sendRequest($psr7Request)
]);

var_dump($responses['first']);
var_dump($responses['second']);
```

### Concurrency Limit

Sending an excessive number of requests may either take up all resources on your side or it may even get you banned by the remote side if it sees an unreasonable number of requests from your side.

As a consequence, it's usually recommended to limit concurrency on the sending side to a reasonable value. It's common to use a rather small limit, as doing more than a dozen of things at once may easily overwhelm the receiving side.

You can use `concurrent` method to set the maximum number of requests to send concurrently. The default value is `25`.

```php
$response = $pool
    ->concurrent(10) // Process up to 10 requests concurrently
    ->send($requests);
```

Additional requests that exceed the concurrency limit will automatically be enqueued until one of the pending requests completes.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email contact@lynh.me instead of using the issue tracker.

## Credits

- [Lynh](https://github.com/jenky)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/fansipan/peak.svg?style=for-the-badge
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=for-the-badge
[ico-gh-actions]: https://img.shields.io/github/actions/workflow/status/phanxipang/peak/testing.yml?branch=main&label=actions&logo=github&style=for-the-badge
[ico-codecov]: https://img.shields.io/codecov/c/github/phanxipang/peak?logo=codecov&style=for-the-badge
[ico-downloads]: https://img.shields.io/packagist/dt/fansipan/peak.svg?style=for-the-badge

[link-packagist]: https://packagist.org/packages/phanxipang/peak
[link-gh-actions]: https://github.com/phanxipang/peak
[link-codecov]: https://codecov.io/gh/phanxipang/peak
[link-downloads]: https://packagist.org/packages/fansipan/peak

