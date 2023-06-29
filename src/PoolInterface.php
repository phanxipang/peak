<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Jenky\Atlas\Response;

interface PoolInterface
{
    public function concurrent(int $concurrency): PoolInterface;

    /**
     * Send concurrent requests.
     *
     * @param  iterable $requests
     * @return array<array-key, Response>
     */
    public function send(iterable $requests): array;
}
