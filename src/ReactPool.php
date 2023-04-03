<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;
use React\Async;

final class ReactPool implements PoolInterface
{
    public function __construct(private ConnectorInterface $connector)
    {
    }

    public function send(iterable $requests): array
    {
        /* $promises = Async\coroutine(function () use ($requests) {
            foreach ($requests as $key => $request) {
                if ($request instanceof Request) {
                    $promise = fn (): Response => $this->connector->send($request);
                } elseif (is_callable($request)) {
                    $promise = $request;
                } else {
                    throw new \InvalidArgumentException('Each value of the iterator must be a Jenky\Atlas\Request or a \Closure that returns a Jenky\Atlas\Response object.');
                }

                yield $key => Async\async($promise)();
            }
        });

        return Async\await($promises); */

        $promises = function () use ($requests) {
            foreach ($requests as $key => $request) {
                if ($request instanceof Request) {
                    $promise = fn (): Response => $this->connector->send($request);
                } elseif (is_callable($request)) {
                    $promise = $request;
                } else {
                    throw new \InvalidArgumentException('Each value of the iterator must be a Jenky\Atlas\Request or a \Closure that returns a Jenky\Atlas\Response object.');
                }

                yield $key => Async\async($promise);
            }
        };

        return Async\await(
            Async\parallel($promises()) // @phpstan-ignore-line
        );
    }
}
