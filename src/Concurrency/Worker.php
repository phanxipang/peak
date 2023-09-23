<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

interface Worker
{
    /**
     * Run the functions in the tasks iterable concurrently, without waiting until the previous function has completed.
     *
     * @template T
     *
     * @param  iterable<array-key, (\Closure(): T)> $tasks
     * @return array<array-key, T>
     */
    public function run(iterable $tasks): array;
}
