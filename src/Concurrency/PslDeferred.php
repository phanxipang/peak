<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Concurrency;

use Psl\Async;

final class PslDeferred implements Deferrable
{
    public function defer(callable $callback, float $delay = 0): mixed
    {
        $defer = new Async\Deferred();

        Async\Scheduler::defer(static function () use ($defer, $callback, $delay) {
            $resolve = static fn (mixed $value) => $defer->complete($value);
            $reject = static fn (\Throwable $e) => $defer->error($e);

            Async\Scheduler::delay($delay, static fn () => $callback($resolve, $reject));
        });

        return $defer->getAwaitable()->await();
    }
}
