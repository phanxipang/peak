<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Pool;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;
use Jenky\Atlas\Util;

final class GuzzlePool implements PoolInterface
{
    public function __construct(private ConnectorInterface $connector)
    {
    }

    public function send(iterable $requests, mixed ...$args): array
    {
        $client = $this->connector->client();

        if (! $client instanceof ClientInterface) {
            throw new \InvalidArgumentException('Client must be Guzzle Client.');
        }

        $promises = function () use ($requests) {
            foreach ($requests as $key => $request) {
                if ($request instanceof Request) {
                    // $promise = fn (): Response => (new AsyncPendingRequest($this->connector, $request))->send();
                    $promise = fn () => $this->connector->client()->sendAsync(Util::request($request));
                } elseif (is_callable($request)) {
                    $promise = $request;
                } else {
                    throw new \InvalidArgumentException('Each value of the iterator must be a Jenky\Atlas\Request or a \Closure that returns a Jenky\Atlas\Response object.');
                }

                yield $key => $promise;
            }
        };

        return Pool::batch($client, $promises()); // with response order

        $out = [];

        $pool = new Pool($client, $promises(), [
            'fulfilled' => function ($response, $i) use (&$out) {
                $out[$i] = new Response($response);
            },
        ]);

        $pool->promise()->wait();

        return $out;
    }
}
