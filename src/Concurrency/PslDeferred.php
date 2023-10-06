<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

use Psl\Async;

final class PslDeferred implements Deferrable
{
    public function defer(callable $callback, float $delay = 0): mixed
    {
        $defer = new Async\Deferred();

        Async\Scheduler::defer(static function () use ($defer, $callback, $delay) {
            Async\Scheduler::delay($delay, static function () use ($defer, $callback) {
                try {
                    $defer->complete($callback());
                } catch (\Throwable $e) {
                    $defer->error($e);
                }
            });
        });

        return $defer->getAwaitable()->await();
    }
}
