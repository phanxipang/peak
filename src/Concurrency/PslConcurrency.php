<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Concurrency;

use Psl\Async;

final class PslConcurrency implements Runner
{
    private readonly Async\Semaphore $semaphore;

    /**
     * @param  int<1, max> $limit
     */
    public function __construct(int $limit = 10, ?\Closure $operation = null)
    {
        if ($limit < 1) {
            throw new \ValueError('Argument #1 ($limit) must be positive, got '.$limit);
        }

        $this->semaphore = new Async\Semaphore(
            $limit, $operation ?? static fn ($value) => $value
        );
    }

    public function run(iterable $tasks): array
    {
        $promises = static function (iterable $tasks, Async\Semaphore $semaphore) {
            foreach ($tasks as $key => $task) {
                if (! \is_callable($task)) {
                    continue;
                }

                yield $key => static fn () => $semaphore->waitFor($task());
            }
        };

        return Async\concurrently($promises($tasks, $this->semaphore)); //@phpstan-ignore-line
    }
}
