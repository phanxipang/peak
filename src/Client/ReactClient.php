<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Client;

use Jenky\Atlas\Pool\Concurrency\Driver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Async;
use React\Http\Browser;

final class ReactClient implements AsyncClientInterface
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

    public function driver(): ?Driver
    {
        return Driver::REACT;
    }
}
