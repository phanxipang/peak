<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Psl\GuzzleClient;
use Jenky\Atlas\Pool\Psl\Pool;
use Jenky\Atlas\Pool\Psl\SymfonyClient;
use Jenky\Concurrency\PoolInterface;

final class PslPoolTest extends TestCase
{
    protected function createPool(ConnectorInterface $connector): PoolInterface
    {
        return new Pool($connector);
    }

    public function test_psl_pool_using_symfony_http_client(): void
    {
        $this->performTests($this->createConnector(new SymfonyClient()));
    }

    public function test_psl_pool_using_guzzle(): void
    {
        $this->performTests($this->createConnector(new GuzzleClient()));
    }
}
