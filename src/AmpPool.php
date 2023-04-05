<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Amp;
use Amp\Future;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;

final class AmpPool implements PoolInterface
{
    public function __construct(private ConnectorInterface $connector)
    {
    }

    public function send(iterable $requests): array
    {
        $promises = function () use ($requests) {
            foreach ($requests as $key => $request) {
                if ($request instanceof Request) {
                    $promise = fn (): Response => $this->connector->send($request);
                } elseif (is_callable($request)) {
                    $promise = $request instanceof \Closure
                        ? $request
                        : \Closure::fromCallable($request);
                } else {
                    throw new \InvalidArgumentException('Each value of the iterator must be a Jenky\Atlas\Request or a \Closure that returns a Jenky\Atlas\Response object.');
                }

                yield $key => Amp\async($promise);
            }
        };

        // @phpstan-ignore-next-line
        return Future\awaitAll($promises())[1] ?? [];
    }
}
