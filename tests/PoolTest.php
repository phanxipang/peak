<?php

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Mock\MockClient;
use Jenky\Atlas\NullConnector;
use Jenky\Atlas\Pool\AmpPool;
use Jenky\Atlas\Pool\Client\ReactClient;
use Jenky\Atlas\Pool\Pool;
use Jenky\Atlas\Pool\ReactPool;
use Jenky\Atlas\Response;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    private EchoConnector $connector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connector = new EchoConnector();
    }

    public function test_pool(): void
    {
        $responses = (new Pool($this->connector))->send([
            new EchoRequest(),
            new EchoRequest('post'),
            new EchoRequest('put'),
        ]);

        $this->assertCount(3, $responses);
    }

    public function test_amp_pool(): void
    {
        $connector = $this->connector->withClient(new MockClient());

        $responses = (new AmpPool($connector))->send([
            fn (): Response => $connector->send(new EchoRequest()),
            fn (): Response => $connector->send(new EchoRequest()),
            fn (): Response => $connector->send(new EchoRequest()),
        ]);

        $this->assertCount(3, $responses);
    }

    public function test_react_pool(): void
    {
        $connector = $this->connector->withClient(new MockClient());

        $pool = new ReactPool($connector);
        $responses = $pool->send([
            'a' => new EchoRequest(),
            'b' => new EchoRequest(),
            'c' => fn (): Response => $connector->send(new EchoRequest()),
        ]);

        $this->assertCount(3, $responses);

        $this->assertArrayHasKey('a', $responses);
        $this->assertArrayHasKey('b', $responses);
        $this->assertArrayHasKey('c', $responses);

        $this->assertInstanceOf(Response::class, $responses['a']);
        $this->assertInstanceOf(Response::class, $responses['b']);
        $this->assertInstanceOf(Response::class, $responses['c']);
    }

    public function test_react_client_pool(): void
    {
        $connector = (new NullConnector())->withClient(new ReactClient());

        $requests = function (int $total) use ($connector) {
            for ($i = 0; $i < $total; $i++) {
                yield fn () => $connector->send(
                    new AkamaiTileRequest($i)
                );
            }
        };

        $responses = (new Pool($connector))->send($requests(30));
        $this->assertCount(30, $responses);
        $this->assertInstanceOf(Response::class, $responses[0]);
    }

    public function test_sending_lots_of_requests(): void
    {
        $connector = new NullConnector();

        for ($i=0; $i < 100; $i++) {
            $requests[] = new AkamaiTileRequest($i);
        }

        $responses = (new Pool($connector))->send($requests);
        $this->assertCount(100, $responses);
        $this->assertInstanceOf(Response::class, $responses[0]);
    }
}
