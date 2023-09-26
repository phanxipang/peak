<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests;

use Fansipan\Peak\Client\GuzzleClient;
use Fansipan\Peak\Client\SymfonyClient;
use Fansipan\Peak\Concurrency\AmpDeferred;

final class AmpPoolTest extends TestCase
{
    private function createSymfonyClient(): SymfonyClient
    {
        return new SymfonyClient(new AmpDeferred());
    }

    private function createGuzzleClient(): GuzzleClient
    {
        return new GuzzleClient(new AmpDeferred());
    }

    public function test_amp_pool_using_symfony_http_client(): void
    {
        $this->performConnectorTests($this->createConnector($this->createSymfonyClient()));
    }

    public function test_amp_pool_using_guzzle(): void
    {
        $this->performConnectorTests($this->createConnector($this->createGuzzleClient()));
    }
}
