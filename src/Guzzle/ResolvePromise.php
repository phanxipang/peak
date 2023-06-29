<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Guzzle;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ResolvePromise
{
    /**
     * @var \Closure
     */
    private $middleware;

    public function __construct(\Closure $middleware)
    {
        $this->middleware = $middleware;
    }

    public function __invoke(RequestInterface $request, callable $next): PromiseInterface
    {
        return $next($request)->then(function (ResponseInterface $response) use ($request) {
            return $this->middleware->__invoke($request, static function () use ($response) {
                return $response;
            });
        });
    }
}
