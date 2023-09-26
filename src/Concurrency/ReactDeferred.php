<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

use React\Async;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

final class ReactDeferred implements Deferrable
{
    private readonly LoopInterface $loop;

    public function __construct(?LoopInterface $loop = null)
    {
        $this->loop = $loop ?: Loop::get();
    }

    public function defer(callable $callback, float $delay = 0): mixed
    {
        $defer = new Deferred();

        $this->loop->futureTick(function () use ($defer, $callback, $delay) {
            $resolve = static fn (mixed $value) => $defer->resolve($value);
            $reject = static fn (\Throwable $e) => $defer->reject($e);

            $this->loop->addTimer($delay, static fn () => $callback($resolve, $reject));
        });

        return Async\await($defer->promise());
    }
}
