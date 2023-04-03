<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use GuzzleHttp\ClientInterface;
use Jenky\Atlas\Contracts\ConnectorInterface;

final class Pool implements PoolInterface
{
    private PoolInterface $delegate;

    public function __construct(ConnectorInterface $connector, ?PoolInterface $pool = null)
    {
        $this->delegate = $pool ?: $this->defaultPool($connector);
    }

    public function send(iterable $requests): array
    {
        return $this->delegate->send($requests);
    }

    /**
     * Get default pool instance.
     */
    private function defaultPool(ConnectorInterface $connector): PoolInterface
    {
        $candidates = [
            // GuzzlePool::class => fn (): bool => $connector->client() instanceof ClientInterface,
            ReactPool::class => fn (): bool => function_exists('React\\Async\\async')
                && function_exists('React\\Async\\await')
                && function_exists('React\\Async\\parallel'),
            AmpPool::class => fn (): bool => function_exists('Amp\\async')
                && function_exists('Amp\\Future\awaitAll'),
        ];

        foreach ($candidates as $pool => $condition) {
            if ($condition()) {
                return new $pool($connector);
            }
        }

        throw new \LogicException('You cannot use the pool feature as the "amphp/parallel" or "react/async" package is not installed.');
    }
}
