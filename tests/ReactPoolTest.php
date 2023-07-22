<?php

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\React\Client;
use Jenky\Atlas\Pool\React\GuzzleClient;
use Jenky\Atlas\Pool\React\Pool;
use Jenky\Atlas\Pool\React\SymfonyClient;
use Jenky\Concurrency\PoolInterface;

final class ReactPoolTest extends TestCase
{
    protected function createPool(ConnectorInterface $connector): PoolInterface
    {
        return new Pool($connector);
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
