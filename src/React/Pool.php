<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\React;

use Clue\React\Mq\Queue;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\PoolTrait;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;
use Jenky\Concurrency\PoolInterface;
use React\Async;

/**
 * @implements PoolInterface<Request|callable(ConnectorInterface): Response, Response>
 */
final class Pool implements PoolInterface
{
    use PoolTrait;

    public function __construct(private ConnectorInterface $connector)
    {
    }

    public function send(iterable $requests): array
    {
        if (\PHP_VERSION_ID >= 80200) {
            // Temporary solution until https://github.com/clue/reactphp-mq/pull/36 release
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        }

        $queue = new Queue($this->concurrency, null, static fn (\Closure $cb) => Async\async($cb)());

        $promises = static function (ConnectorInterface $connector) use ($requests, $queue) {
            foreach ($requests as $key => $request) {
                if ($request instanceof Request) {
                    yield $key => static fn () => $queue(static fn (): Response => $connector->send($request));
                } elseif (is_callable($request)) {
                    yield $key => static fn () => $queue(static fn (): Response => $request($connector));
                } else {
                    throw new \InvalidArgumentException('Each value of the iterator must be a Jenky\Atlas\Request or a \Closure that returns a Jenky\Atlas\Response object.');
                }
            }
        };

        return Async\await(Async\parallel($promises($this->connector)));
    }
}
