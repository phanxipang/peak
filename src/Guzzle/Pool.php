<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Guzzle;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pipeline;
use Jenky\Atlas\Pool\PoolInterface;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;
use Jenky\Atlas\Util;
use Psr\Http\Message\RequestInterface;

final class Pool implements PoolInterface
{
    /**
     * @var ConnectorInterface
     */
    private $connector;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var int
     */
    private $concurrency = 25;

    public function __construct(ConnectorInterface $connector)
    {
        $client = $connector->client();

        if (! $client instanceof ClientInterface) {
            throw new \InvalidArgumentException(sprintf('Client must be Guzzle Client. Instance of %s given.', get_class($client)));
        }

        $this->connector = $connector;
        $this->client = $client;
    }

    public function concurrent(int $concurrency): PoolInterface
    {
        if ($concurrency < 1) {
            throw new \ValueError('Argument #1 ($concurrency) must be positive, got '.$concurrency);
        }

        $clone = clone $this;
        $clone->concurrency = $concurrency;

        return $clone;
    }

    public function send(iterable $requests): array
    {
        $baseUri = method_exists($this->connector, 'baseUri')
            ? $this->connector->baseUri()
            : null;

        $promises = function () use ($requests, $baseUri) {
            foreach ($requests as $key => $request) {
                if ($request instanceof Request) {
                    yield $key => $this->sendAsync(Util::request($request, $baseUri));
                } elseif ($request instanceof RequestInterface) {
                    yield $key => $this->sendAsync($request);
                } elseif (is_callable($request)) {
                    yield $key => $request();
                } else {
                    throw new \InvalidArgumentException('Each value yielded by the iterator must be a Psr7\Http\Message\RequestInterface or a callable that returns a promise that fulfills with a Psr7\Message\Http\ResponseInterface object.');
                }
            }
        };

        $out = [];

        $cb = static function ($response, $i) use (&$out) {
            $out[$i] = new Response($response);
        };

        $pool = new EachPromise($promises(), [
            'concurrency' => $this->concurrency,
            PromiseInterface::FULFILLED => $cb,
            PromiseInterface::REJECTED => $cb,
        ]);

        $pool->promise()->wait();

        return $out;
    }

    private function sendAsync(RequestInterface $request): PromiseInterface
    {
        return (new Pipeline())
            ->send($request)
            ->through($this->gatherMiddleware())
            ->then(function (RequestInterface $request) {
                return $this->client->sendAsync($request, [
                    RequestOptions::ALLOW_REDIRECTS => false,
                    RequestOptions::HTTP_ERRORS => false,
                ]);
            });
    }

    /**
     * Gather all the middleware.
     */
    private function gatherMiddleware(): array
    {
        return array_filter(array_map(static function (array $item) {
            if (empty($item[0])) {
                return null;
            }

            return new ResolvePromise(
                $item[0] instanceof \Closure
                    ? $item[0]
                    : \Closure::fromCallable($item[0])
            );
        }, $this->connector->middleware()->all()));
    }
}
