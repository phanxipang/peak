<?php

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Middleware\Interceptor;
use Jenky\Atlas\NullConnector;
use Jenky\Atlas\Pool\PoolFactory;
use Jenky\Atlas\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class PoolTest extends TestCase
{
    public function test_pool(): void
    {
        $responses = PoolFactory::create(new EchoConnector())->send([
            new EchoRequest(),
            new EchoRequest('post'),
            new EchoRequest('put'),
        ]);

        $this->assertCount(3, $responses);
    }

    public function test_sending_lots_of_requests(): void
    {
        $connector = new NullConnector();

        $connector->middleware()->push(
            Interceptor::response(function (ResponseInterface $response) {
                return $response->withHeader('X-Foo', 'baz');
            })
        );

        for ($i=0; $i < 100; $i++) {
            $requests[] = new AkamaiTileRequest($i);
        }

        $responses = PoolFactory::create($connector)->send($requests);
        $this->assertCount(100, $responses);
        $this->assertInstanceOf(Response::class, $responses[0]);
    }
}
