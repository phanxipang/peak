<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\React;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Exceptions\UnsupportedException;
use Jenky\Atlas\Pool\PoolInterface;
use Jenky\Atlas\Pool\PoolTrait;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;
use React\Async;
use React\Http\Browser;
use React\Promise;

final class Pool implements PoolInterface
{
    use PoolTrait;

    private ConnectorInterface $connector;

    public function __construct(ConnectorInterface $connector)
    {
        if ($connector->client() instanceof Client) {
            $this->connector = clone $connector;
        } elseif (method_exists($connector, 'withClient')) {
            $this->connector = $connector->withClient(new Client(new Browser()));
        } else {
            throw new UnsupportedException('The client is not supported.');
        }
    }

    public function send(iterable $requests): array
    {
        $promises = static function (ConnectorInterface $connector) use ($requests) {
            foreach ($requests as $key => $request) {
                if ($request instanceof Request) {
                    yield $key => Async\async(fn (): Response => $connector->send($request));
                } elseif (is_callable($request)) {
                    yield $key => Async\async($request);
                } else {
                    throw new \InvalidArgumentException('Each value of the iterator must be a Jenky\Atlas\Request or a \Closure that returns a Jenky\Atlas\Response object.');
                }
            }
        };

        return Async\await(Promise\all(Async\parallel($promises($this->connector)))); //@phpstan-ignore-line
    }
}
