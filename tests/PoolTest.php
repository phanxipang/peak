<?php

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\NullConnector;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    public function test_pool(): void
    {
        $pool = new NullPool(new NullConnector());

        $responses = $pool->send([
            new DummyRequest(),
            new DummyRequest(),
        ]);

        $this->assertCount(0, $responses);
    }

    public function test_concurrent(): void
    {
        $pool = new NullPool(new NullConnector());

        $this->expectException(\ValueError::class);

        $pool->concurrent(-1);

        $this->assertNotSame($pool, $pool->concurrent(10), 'Pool is immutable');
    }
}
