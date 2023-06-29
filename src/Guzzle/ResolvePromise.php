<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Guzzle;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ResolvePromise
{
    public function __construct(private \Closure $middleware)
    {
    }

    public function __invoke(RequestInterface $request, callable $next): PromiseInterface
    {
        return $next($request)->then(
            fn (ResponseInterface $response) => $this->middleware->__invoke($request, fn () => $response)
        );
    }
}
