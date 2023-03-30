<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Client;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Async;
use React\Http\Browser;

final class ReactClient implements ClientInterface
{
    private Browser $browser;

    public function __construct(?Browser $browser = null)
    {
        $this->browser = $browser ?? new Browser();
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
