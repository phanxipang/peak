<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class FakeHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return Psr17FactoryDiscovery::findResponseFactory()->createResponse();
    }
}
