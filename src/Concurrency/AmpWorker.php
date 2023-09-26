<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

use Amp;
use Amp\Future;
use Amp\Pipeline\Pipeline;

final class AmpWorker implements Worker
{
    /**
     * @param  int<1, max> $limit
     */
    public function __construct(private readonly int $limit = 10)
    {
        if ($limit < 1) {
            throw new \ValueError('Argument #1 ($limit) must be positive, got '.$limit);
        }
    }

    public function run(iterable $tasks): array
    {
        $promises = Pipeline::fromIterable($tasks)
            ->concurrent($this->limit)
            ->unordered()
            ->map(static fn (\Closure $task) => Amp\async($task));

        return Future\awaitAll($promises)[1] ?? [];
    }
}
