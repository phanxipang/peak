<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Psl;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Exception\UnsupportedClientException;
use Jenky\Atlas\Pool\PoolTrait;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;
use Jenky\Concurrency\PoolInterface;
use Psl\Async;

/**
 * @implements PoolInterface<Request|callable(ConnectorInterface): Response, Response>
 */
final class Pool implements PoolInterface
{
    use PoolTrait;

    public function __construct(private ConnectorInterface $connector)
    {
        if (! $connector->client() instanceof AsyncClientInterface) {
            // @codeCoverageIgnoreStart
            throw new UnsupportedClientException(\sprintf(
                'The client %s is not supported. Please swap the underlying client to supported one.',
                \get_debug_type($connector->client())
            ));
            // @codeCoverageIgnoreEnd
        }
    }

    public function send(iterable $requests): array
    {
        $semaphore = new Async\Semaphore($this->concurrency, static fn (Response $response) => $response);

        $promises = static function (ConnectorInterface $connector) use ($requests, $semaphore) {
            foreach ($requests as $key => $request) {
                if ($request instanceof Request) {
                    yield $key => static fn (): Response => $semaphore->waitFor($connector->send($request));
                } elseif (\is_callable($request)) {
                    yield $key => static fn (): Response => $semaphore->waitFor($request($connector));
                } else {
                    throw new \InvalidArgumentException('Each value of the iterator must be a Jenky\Atlas\Request or a \Closure that returns a Jenky\Atlas\Response object.');
                }
            }
        };

        return Async\concurrently($promises($this->connector)); //@phpstan-ignore-line
    }
}
