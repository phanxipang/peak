<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\React;

use Jenky\Atlas\Pool\AsyncClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Async;
use React\Http\Browser;

final class Client implements AsyncClientInterface
{
    private Browser $browser;

    public function __construct(?Browser $browser = null)
    {
        $this->browser = ($browser ?? new Browser())
            ->withFollowRedirects(false)
            ->withRejectErrorResponse(false);
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
