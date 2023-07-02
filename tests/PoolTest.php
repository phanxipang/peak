<?php

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Pool\Guzzle\Pool as GuzzlePool;
use Jenky\Atlas\Pool\PoolFactory;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    public function test_factory(): void
    {
        $pool = PoolFactory::create(new EchoConnector());

        $this->assertInstanceOf(GuzzlePool::class, $pool);
    }
}
