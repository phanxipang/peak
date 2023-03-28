<?php

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Mock\MockClient;
use Jenky\Atlas\Pool\AmpPool;
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
        $connector = $this->connector->withClient(new MockClient());

        $responses = $connector->pool([
            new EchoRequest(),
            new EchoRequest('post'),
            new EchoRequest('put'),
        ])->send();

        $this->assertCount(3, $responses);
    }

    public function test_amp_pool(): void
    {
        $connector = $this->connector->withClient(new MockClient());

        $pool = $connector->pool([
            function () use ($connector): Response {
                return $connector->send(new EchoRequest());
            },
            function () use ($connector): Response {
                return $connector->send(new EchoRequest());
            },
            function () use ($connector): Response {
                return $connector->send(new EchoRequest());
            },
        ], $amp = new AmpPool());

        $this->assertSame($amp, $pool);
        $this->assertCount(3, $pool->send());
    }

    public function test_react_pool(): void
    {
        $connector = $this->connector->withClient(new MockClient());

        $pool = $connector->pool([
            'a' => new EchoRequest(),
            'b' => new EchoRequest(),
            'c' => function () use ($connector): Response {
                return $connector->send(new EchoRequest());
            },
        ], $react = new ReactPool());

        $this->assertSame($react, $pool);
        $this->assertCount(3, $responses = $pool->send());

        $this->assertArrayHasKey('a', $responses);
        $this->assertArrayHasKey('b', $responses);
        $this->assertArrayHasKey('c', $responses);

        $this->assertInstanceOf(Response::class, $responses['a']);
        $this->assertInstanceOf(Response::class, $responses['b']);
        $this->assertInstanceOf(Response::class, $responses['c']);
    }
}
