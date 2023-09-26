<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

interface Deferrable
{
    /**
     * Defer the operation with optional delay in seconds.
     *
     * @template T
     *
     * @param  callable(): T $callback
     * @return T
     */
    public function defer(callable $callback, float $delay = 0): mixed;
}
