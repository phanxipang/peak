<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

interface Runner
{
    /**
     * Run the functions in the tasks iterable concurrently, without waiting until the previous function has completed.
     *
     * @template Tv
     *
     * @param  iterable<array-key, (\Closure(): Tv)> $tasks
     * @return array<array-key, Tv>
     */
    public function run(iterable $tasks): array;
}
