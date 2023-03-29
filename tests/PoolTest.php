<?php

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Connector;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Mock\MockClient;
use Jenky\Atlas\Pool\AmpPool;
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

        $this->assertTrue(true);
    }

    public function test_amp_pool(): void
    {
        $connector = $this->connector->withClient(new MockClient());

        $responses = (new Pool($connector, new AmpPool()))->send([
            fn (ConnectorInterface $connector): Response => $connector->send(new EchoRequest()),
            fn (ConnectorInterface $connector): Response => $connector->send(new EchoRequest()),
            fn (ConnectorInterface $connector): Response => $connector->send(new EchoRequest()),
        ]);

        $this->assertCount(3, $responses);
    }

    public function test_react_pool(): void
    {
        $connector = $this->connector->withClient(new MockClient());

        $pool = (new Pool($connector, new ReactPool()));
        $responses = $pool->send([
            'a' => new EchoRequest(),
            'b' => new EchoRequest(),
            'c' => fn (ConnectorInterface $connector): Response => $connector->send(new EchoRequest()),
        ]);

        $this->assertCount(3, $responses);

        $this->assertArrayHasKey('a', $responses);
        $this->assertArrayHasKey('b', $responses);
        $this->assertArrayHasKey('c', $responses);

        $this->assertInstanceOf(Response::class, $responses['a']);
        $this->assertInstanceOf(Response::class, $responses['b']);
        $this->assertInstanceOf(Response::class, $responses['c']);
    }
}
