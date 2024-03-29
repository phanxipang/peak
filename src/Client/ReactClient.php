<?php

declare(strict_types=1);

namespace Fansipan\Peak\Client;

use Fansipan\Peak\Concurrency\Driver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Async;
use React\Http\Browser;

final class ReactClient implements AsyncClientInterface, Delayable
{
    use DelayTrait;

    private Browser $browser;

    public function __construct(?Browser $browser = null)
    {
        $this->browser = ($browser ?? new Browser())
            ->withFollowRedirects(false)
            ->withRejectErrorResponse(false);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $delay = $this->getDelayAsSeconds();

        if ($delay > 0) {
            Async\delay($delay);
        }

        $this->delay = 0;

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
