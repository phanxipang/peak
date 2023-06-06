<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;
use React\Async;
use React\Promise;

final class ReactPool implements PoolInterface
{
    public function __construct(private ConnectorInterface $connector)
    {
    }

    public function send(iterable $requests): array
    {
        /* $promise = Async\coroutine(function ($requests) {
            $promises = [];
            foreach ($requests as $key => $request) {
                if ($request instanceof Request) {
                    $fetch = fn (): Response => $this->connector->send($request);
                } elseif (is_callable($request)) {
                    $fetch = $request;
                } else {
                    throw new \InvalidArgumentException('Each value of the iterator must be a Jenky\Atlas\Request or a \Closure that returns a Jenky\Atlas\Response object.');
                }

                // yield $key => Async\async($fetch);
                $promises[$key] = Async\async($fetch);

            }
            return Async\parallel($promises);
        }, $requests);

        return Async\await($promise); */

        $promises = function ($requests) {
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
            Async\parallel($promises($requests)) // @phpstan-ignore-line
        );
    }
}
