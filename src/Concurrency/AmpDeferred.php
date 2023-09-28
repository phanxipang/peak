<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

use Amp;

final class AmpDeferred implements Deferrable
{
    public function defer(callable $callback, float $delay = 0): mixed
    {
        return Amp\async(static function (callable $callback, float $delay) {
            if ($delay > 0) {
                Amp\delay($delay);
            }

            return $callback();
        }, $callback, $delay)->await();
    }
}
