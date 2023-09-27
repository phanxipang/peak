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
        $promises = Pipeline::fromIterable(static function () use ($tasks): \Generator {
            foreach ($tasks as $key => $task) {
                yield new AmpTask($key, $task);
            }
        })
            ->concurrent($this->limit)
            ->unordered()
            ->map(static fn (AmpTask $task) => Amp\async(\Closure::fromCallable($task)));

        $results = [];

        foreach (Future::iterate($promises) as $promise) {
            try {
                /** @var AmpTask $t */
                $t = $promise->await();
                $results[$t->key()] = $t->value();
            } catch (\Throwable) {
            }
        }

        return $results;
    }
}
