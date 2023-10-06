<?php

namespace Fansipan\Peak\Tests;

use Fansipan\Peak\Client\GuzzleClient;
use Fansipan\Peak\Client\ReactClient;
use Fansipan\Peak\Client\SymfonyClient;
use Fansipan\Peak\Concurrency\ReactDeferred;

final class ReactPoolTest extends TestCase
{
    private function createSymfonyClient(): SymfonyClient
    {
        return new SymfonyClient(new ReactDeferred());
    }

    private function createGuzzleClient(): GuzzleClient
    {
        return new GuzzleClient(new ReactDeferred());
    }

    public function test_react_pool_using_react_browser(): void
    {
        $this->performConnectorTests($this->createConnector(new ReactClient()));
    }

    public function test_react_pool_using_symfony_http_client(): void
    {
        $this->performConnectorTests($this->createConnector($this->createSymfonyClient()));
    }

    public function test_react_pool_using_guzzle(): void
    {
        $this->performConnectorTests($this->createConnector($this->createGuzzleClient()));
    }

    public function test_react_pool_keyed_response(): void
    {
        $this->performKeyedResponseTests(
            $this->mockSymfonyClient(new ReactDeferred())
        );
    }
}
