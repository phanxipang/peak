<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\React;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Async;
use React\Http\Browser;

final class Client implements ClientInterface
{
    public function __construct(private Browser $browser)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return Async\await(
            $this->browser->request(
                $request->getMethod(),
                (string) $request->getUri(),
                $request->getHeaders(),
                (string) $request->getBody()
            )
        );
    }
}
