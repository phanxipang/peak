<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

/**
 * @template-covariant T
 */
interface Deferrable
{
    /**
     * Defer the operation with optional delay in seconds.
     *
     * @param  callable(\Closure(T): void, \Closure(\Throwable): void): void $callback
     * @parma  float<0, max> $delay
     * @return T
     */
    public function defer(callable $callback, float $delay = 0): mixed;
}
