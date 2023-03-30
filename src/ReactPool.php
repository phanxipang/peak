<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use React\Async;
use React\Promise;

final class ReactPool implements PoolInterface
{
    /**
     * @var array<array-key, callable(): \React\Promise\PromiseInterface>
     */
    private array $requests = [];

    public function queue($key, callable $request, mixed ...$args): void
    {
        // $this->requests[$key] = Async\async(fn () => Async\await(
        //     Async\async($request)(...$args)
        // ));
        $this->requests[$key] = Async\async($request);
    }

    public function send(): array
    {
        return Async\await(Async\parallel($this->requests));
    }
}
