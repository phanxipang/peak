<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Tests;

use Fansipan\Concurrent\Client\GuzzleClient;
use Fansipan\Concurrent\Client\SymfonyClient;
use Fansipan\Concurrent\Concurrency\PslDeferred;

final class PslPoolTest extends TestCase
{
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
