<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Pool\Client\AsyncClientInterface;
use Jenky\Atlas\Pool\Client\GuzzleClient;
use Jenky\Atlas\Pool\Client\SymfonyClient;
use Jenky\Atlas\Pool\Concurrency\PslDeferred;
use Jenky\Atlas\Pool\PoolInterface;
use Jenky\Atlas\Pool\PslPool;

final class PslPoolTest extends TestCase
{
    protected function createPoolFromClient(AsyncClientInterface $client): PoolInterface
    {
        return new PslPool($client);
    }

    private function createSymfonyClient(): SymfonyClient
    {
        return new SymfonyClient(new PslDeferred());
    }

    private function createGuzzleClient(): GuzzleClient
    {
        return new GuzzleClient(new PslDeferred());
    }

    public function test_react_pool_using_symfony_http_client(): void
    {
        $this->performConnectorTests($this->createConnector($this->createSymfonyClient()));
    }

    public function test_react_pool_using_guzzle(): void
    {
        $this->performConnectorTests($this->createConnector($this->createGuzzleClient()));
    }
}
