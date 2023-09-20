<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Concurrency;

use Psl\Async;

final class PslDeferred implements Deferrable
{
    public function defer(callable $callback): mixed
    {
        $defer = new Async\Deferred();

        Async\Scheduler::defer(static function () use ($defer, $callback) {
            $resolve = static fn (mixed $value) => $defer->complete($value);
            $reject = static fn (\Throwable $e) => $defer->error($e);
            $callback($resolve, $reject);
        });

        return $defer->getAwaitable()->await();
    }
}
