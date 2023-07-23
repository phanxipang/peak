<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Psl;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;
use Jenky\Concurrency\PoolInterface;
use Psl\Async;

/**
 * @implements PoolInterface<Request|callable(ConnectorInterface): Response, Response>
 */
final class Pool implements PoolInterface
{
    public function __construct(private ConnectorInterface $connector)
    {
    }

    /**
     * @codeCoverageIgnore
     *
     * @throws \ValueError
     */
    public function concurrent(int $concurrency): PoolInterface
    {
        @trigger_error('Psl pool does not support concurrency limit.', E_USER_WARNING);

        if ($concurrency < 1) {
            throw new \ValueError('Argument #1 ($concurrency) must be positive, got '.$concurrency);
        }

        return $this;
    }

    public function send(iterable $requests): array
    {
        $promises = static function (ConnectorInterface $connector) use ($requests) {
            foreach ($requests as $key => $request) {
                if ($request instanceof Request) {
                    yield $key => static fn (): Response => $connector->send($request);
                } elseif (is_callable($request)) {
                    yield $key => static fn (): Response => $request($connector);
                } else {
                    throw new \InvalidArgumentException('Each value of the iterator must be a Jenky\Atlas\Request or a \Closure that returns a Jenky\Atlas\Response object.');
                }
            }
        };

        return Async\concurrently($promises($this->connector)); //@phpstan-ignore-line
    }
}
