<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;
use Jenky\Concurrency\PoolInterface;
use Jenky\Concurrency\PoolTrait;

/**
 * @implements PoolInterface<Request|callable(ConnectorInterface): Response, Response>
 */
final class NullPool implements PoolInterface
{
    use PoolTrait;

    /**
     * @param  array<array-key, Response>
     */
    public function __construct(
        private array $responses = []
    ) {
    }

    public function send(iterable $requests): array
    {
        return $this->responses;
    }
}
