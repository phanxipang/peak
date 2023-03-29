<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;

final class Pool
{
    private PoolInterface $delegate;

    public function __construct(
        private ConnectorInterface $connector,
        ?PoolInterface $pool = null
    ) {
        $this->delegate = $pool ?: $this->defaultPool();
    }

    /**
     * Send concurrent requests.
     *
     * @param  iterable<\Jenky\Atlas\Request|callable(ConnectorInterface): \Jenky\Atlas\Response> $requests
     * @return array<array-key, \Jenky\Atlas\Response>
     */
    public function send(iterable $requests): array
    {
        foreach ($requests as $key => $request) {
            if ($request instanceof Request) {
                $promise = fn (ConnectorInterface $connector): Response => $connector->send($request);
            } elseif (is_callable($request)) {
                $promise = $request;
            } else {
                throw new \InvalidArgumentException('Each value of the iterator must be a Jenky\Atlas\Request or a \Closure that returns a Jenky\Atlas\Response object.');
            }

            $this->delegate->queue($key, $promise, clone $this->connector); // @phpstan-ignore-line
        }

        return $this->delegate->send();
    }

    /**
     * Get default pool instance.
     */
    private function defaultPool(): PoolInterface
    {
        $candidates = [
            ReactPool::class => fn (): bool => function_exists('React\\Async\\async')
                && function_exists('React\\Async\\await')
                && function_exists('React\\Async\\parallel'),
            AmpPool::class => fn (): bool => function_exists('Amp\\async')
                && function_exists('Amp\\Future\awaitAll'),
        ];

        foreach ($candidates as $pool => $condition) {
            if ($condition()) {
                return new $pool();
            }
        }

        throw new \LogicException('You cannot use the pool feature as the "amphp/parallel" or "react/async" package is not installed.');
    }
}
