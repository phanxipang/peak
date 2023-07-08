<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

/**
 * @template TReq
 * @template TRes
 */
interface PoolInterface
{
    /**
     * Set the maximum number of requests to send concurrently.
     */
    public function concurrent(int $concurrency): PoolInterface;

    /**
     * Send concurrent requests.
     *
     * @param  iterable<TReq> $requests
     * @return array<array-key, TRes>
     */
    public function send(iterable $requests): array;
}
