<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Concurrency;

interface Deferrable
{
    /**
     * @template T
     *
     * @param  callable(\Closure(T), \Closure(\Throwable)) $callback
     * @return T
     */
    public function defer(callable $callback): mixed;
}
