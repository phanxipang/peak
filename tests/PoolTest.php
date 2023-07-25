<?php

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\NullConnector;
use Jenky\Atlas\Pool\Exception\UnsupportedFeatureException;
use Jenky\Atlas\Pool\PoolFactory;
use PHPUnit\Framework\TestCase;

final class PoolTest extends TestCase
{
    public function test_pool(): void
    {
        $pool = new NullPool();

        $responses = $pool->send([
            new DummyRequest(),
            new DummyRequest(),
        ]);

        $this->assertCount(0, $responses);
    }

    public function test_concurrent(): void
    {
        $pool = new NullPool();

        $pool->concurrent(10);

        $this->assertNotSame($pool, $pool->concurrent(10), 'Pool is immutable');

        $this->expectException(\ValueError::class);

        $pool->concurrent(-1);
    }

    public function test_factory(): void
    {
        $factory = new PoolFactory();

        $reflection = new \ReflectionClass($factory);
        $reflection->setStaticPropertyValue('candidates', []);

        $this->expectException(UnsupportedFeatureException::class);

        $factory->createPool(new NullConnector());
    }

    private function createFactory(string $method): PoolFactory
    {
        $factory = new PoolFactory();

        $reflection = new \ReflectionClass($factory);

        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        $reflection->setStaticPropertyValue('candidates', [fn (ConnectorInterface $connector) => $method->invoke($factory, $connector)]);

        return $factory;
    }

    public function test_factory_react(): void
    {
        $pool = $this->createFactory('createReactPool')->createPool(new NullConnector());

        $this->assertInstanceOf(\Jenky\Atlas\Pool\React\Pool::class, $pool);
    }

    public function test_factory_psl(): void
    {
        $pool = $this->createFactory('createPslPool')->createPool(new NullConnector());

        $this->assertInstanceOf(\Jenky\Atlas\Pool\Psl\Pool::class, $pool);
    }
}
