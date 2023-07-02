<?php

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Middleware\Interceptor;
use Jenky\Atlas\NullConnector;
use Jenky\Atlas\Pool\React\Pool;
use Jenky\Atlas\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ReactPoolTest extends TestCase
{
    public function test_react_pool(): void
    {
        $responses = (new Pool(new EchoConnector()))->send([
            fn (ConnectorInterface $connector) => $connector->send(new EchoRequest()),
            new EchoRequest('post'),
            new EchoRequest('put'),
        ]);

        $this->assertCount(3, $responses);
    }

    public function test_react_sending_lots_of_requests(): void
    {
        $connector = new NullConnector();

        $connector->middleware()->push(
            Interceptor::response(function (ResponseInterface $response) {
                return $response->withHeader('X-Foo', 'bar');
            })
        );

        $requests = static function (int $total) {
            for ($i=1; $i <= $total; $i++) {
                yield new AkamaiTileRequest($i);
            }
        };

        $responses = (new Pool($connector))->send($requests(100));
        $this->assertCount(100, $responses);
        $this->assertInstanceOf(Response::class, $responses[0]);
        $this->assertSame('bar', $responses[0]->header('X-Foo'));
    }
}
