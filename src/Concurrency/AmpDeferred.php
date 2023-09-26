<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

use Amp;

final class AmpDeferred implements Deferrable
{
    public function defer(callable $callback, float $delay = 0): mixed
    {
        if ($delay > 0) {
            Amp\delay($delay);
        }

        return Amp\async(
            $callback instanceof \Closure ? $callback : \Closure::fromCallable($callback)
        )->await();
    }
}
