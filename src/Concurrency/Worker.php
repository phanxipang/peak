<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

interface Worker
{
    /**
     * Run the functions in the tasks iterable concurrently, without waiting until the previous function has completed.
     *
     * @template Tk of array-key
     * @template Tv
     *
     * @param  iterable<Tk, \Closure(): Tv> $tasks
     * @return array<Tk, Tv>
     */
    public function run(iterable $tasks): array;
}
