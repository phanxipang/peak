<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use React\Async;

final class ReactPool implements PoolInterface
{
    /**
     * @var array<array-key, callable>
     */
    private array $requests = [];

    public function queue($key, callable $request, mixed ...$args): void
    {
        $this->requests[$key] = Async\async(function () use ($args, $request) {
            return Async\await(Async\async($request)(...$args));
        });
    }

    public function send(): array
    {
        return Async\await(Async\parallel($this->requests));
    }
}
