<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

trait PoolTrait
{
    /**
     * @var int<1, max>
     */
    private int $concurrency = 25;

    /**
     * @param  int<1, max> $concurrency
     *
     * @throws \ValueError
     */
    public function concurrent(int $concurrency): PoolInterface
    {
        if ($concurrency < 1) {
            throw new \ValueError('Argument #1 ($concurrency) must be positive, got '.$concurrency);
        }

        $clone = clone $this;
        $clone->concurrency = $concurrency;

        return $clone;
    }
}
