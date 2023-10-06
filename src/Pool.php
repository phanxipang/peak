<?php

declare(strict_types=1);

namespace Fansipan\Peak;

/**
 * @template TReq
 * @template TRes
 */
interface Pool
{
    /**
     * Set the maximum number of requests to send concurrently.
     *
     * @return static
     */
    public function concurrent(int $concurrency): Pool;

    /**
     * Send concurrent requests.
     *
     * @template Tk of array-key
     *
     * @param  iterable<Tk, TReq> $requests
     * @return array<Tk, TRes>
     */
    public function send(iterable $requests): array;
}
