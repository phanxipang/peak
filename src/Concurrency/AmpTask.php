<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

/**
 * @template T
 */
final class AmpTask
{
    /**
     * @var T
     */
    private mixed $value = null;

    /**
     * @param  \Closure(): T $task
     */
    public function __construct(
        private readonly string|int $key,
        private readonly \Closure $task
    ) {
    }

    public function key(): string|int
    {
        return $this->key;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    public function __invoke(): self
    {
        $this->value = ($this->task)();

        return $this;
    }
}
