<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests;

use Fansipan\Peak\Client\GuzzleClient;
use Fansipan\Peak\Client\SymfonyClient;
use Fansipan\Peak\Concurrency\Deferrable;
use Fansipan\Util;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Symfony\Component\HttpClient\MockHttpClient;

trait TestTrait
{
    protected function createRequests(int $total): iterable
    {
        for ($i = 1; $i <= $total; $i++) {
            yield new AkamaiTileRequest($i);
        }
    }

    protected function createPsrRequests(int $total): iterable
    {
        for ($i = 1; $i <= $total; $i++) {
            yield Util::request(new AkamaiTileRequest($i));
        }
    }

    protected function mockGuzzleClient(Deferrable $defer, ?array $response = null): GuzzleClient
    {
        $handler = new MockHandler($response);
        $handlerStack = HandlerStack::create($handler);

        return new GuzzleClient($defer, new Client(['handler' => $handlerStack]));
    }

    protected function mockSymfonyClient(Deferrable $defer, mixed $response = null): SymfonyClient
    {
        return new SymfonyClient($defer, new MockHttpClient($response));
    }
}
