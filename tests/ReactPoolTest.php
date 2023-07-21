<?php

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Middleware\Interceptor;
use Jenky\Atlas\NullConnector;
use Jenky\Atlas\Pool\React\Client;
use Jenky\Atlas\Pool\React\GuzzleClient;
use Jenky\Atlas\Pool\React\Pool;
use Jenky\Atlas\Pool\React\SymfonyClient;
use Jenky\Atlas\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

class ReactPoolTest extends TestCase
{
    private function createRequests(int $total): iterable
    {
        for ($i=1; $i <= $total; $i++) {
            yield new AkamaiTileRequest($i);
        }
    }

    private function createConnector(ClientInterface $client): ConnectorInterface
    {
        return (new NullConnector())->withClient($client);
    }

    private function performTests(ConnectorInterface $connector): void
    {
        $connector->middleware()->push(
            Interceptor::response(function (ResponseInterface $response) {
                return $response->withHeader('X-Foo', 'bar');
            })
        );

        $responses = (new Pool($connector))->send($this->createRequests(100));

        $this->assertCount(100, $responses);
        $this->assertInstanceOf(Response::class, $responses[0]);
        $this->assertSame('bar', $responses[0]->header('X-Foo'));
    }

    public function test_react_pool_using_react_browser(): void
    {
        $this->performTests($this->createConnector(new Client()));
    }

    public function test_react_pool_using_symfony_http_client(): void
    {
        $this->performTests($this->createConnector(new SymfonyClient()));
    }

    public function test_react_pool_using_guzzle(): void
    {
        $this->performTests($this->createConnector(new GuzzleClient()));
    }
}
