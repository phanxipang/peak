<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

use Clue\React\Mq\Queue;
use React\Async;

final class ReactWorker implements Worker
{
    private readonly Queue $queue;

    /**
     * @param  int<1, max> $limit
     */
    public function __construct(int $limit = 10)
    {
        if ($limit < 1) {
            throw new \ValueError('Argument #1 ($limit) must be positive, got '.$limit);
        }

        $this->queue = new Queue(
            $limit, null, static fn (\Closure $cb) => Async\async($cb)()
        );
    }

    public function run(iterable $tasks): array
    {
        $promises = static function (iterable $tasks, Queue $queue) {
            foreach ($tasks as $key => $task) {
                if (! \is_callable($task)) {
                    continue;
                }

                yield $key => static fn () => $queue($task);
            }
        };

        return Async\await(Async\parallel($promises($tasks, $this->queue)));
    }
}
