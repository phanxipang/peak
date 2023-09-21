<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Concurrency;

/**
 * @template-covariant T
 */
interface Deferrable
{
    /**
     * Defer the operation.
     *
     * @param  callable(\Closure(T): void, \Closure(\Throwable): void): void $callback
     * @return T
     */
    public function defer(callable $callback): mixed;
}
