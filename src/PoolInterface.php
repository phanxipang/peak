<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

interface PoolInterface
{
    /**
     * Send concurrent requests.
     *
     * @param  iterable<callable> $requests
     * @return array<array-key, mixed>
     */
    public function send(iterable $requests): array;
}
