<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests;

use Fansipan\Mock\MockResponse;
use Fansipan\Peak\Client\AsyncClientInterface;
use Fansipan\Peak\Client\Delayable;
use Fansipan\Peak\Client\ReactClient;
use Fansipan\Peak\Concurrency\PslDeferred;
use Fansipan\Peak\Concurrency\ReactDeferred;
use Fansipan\Peak\PoolFactory;
use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;

final class DelayTest extends TestCase
{
    use TestTrait;

    private RequestFactoryInterface $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
    }

    public function test_amp_delay(): void
    {
        $request = $this->requestFactory->createRequest('GET', 'http://localhost');

        $client = $this->mockGuzzleClient(new PslDeferred(), [
            MockResponse::create(''),
        ]);

        $reflection = new \ReflectionProperty($client, 'delay');
        $reflection->setAccessible(true);

        $client->delay(1000);

        $this->assertSame(1000, $reflection->getValue($client));

        $client->sendRequest($request);

        $this->assertSame(0, $reflection->getValue($client));
    }

    public function test_psl_delay(): void
    {
        $request = $this->requestFactory->createRequest('GET', 'http://localhost');

        $client = $this->mockGuzzleClient(new PslDeferred(), [
            MockResponse::create(''),
        ]);

        $reflection = new \ReflectionProperty($client, 'delay');
        $reflection->setAccessible(true);

        $client->delay(1000);

        $this->assertSame(1000, $reflection->getValue($client));

        $client->sendRequest($request);

        $this->assertSame(0, $reflection->getValue($client));
    }

    public function test_react_delay(): void
    {
        $request = $this->requestFactory->createRequest('GET', 'https://example.com');

        $client = $this->mockSymfonyClient(new ReactDeferred());

        $reflection = new \ReflectionProperty($client, 'delay');
        $reflection->setAccessible(true);

        $client->delay(1000);

        $this->assertSame(1000, $reflection->getValue($client));

        $client->sendRequest($request);

        $this->assertSame(0, $reflection->getValue($client));

        $client = new ReactClient();
        $client->delay(1000);
        $client->sendRequest($request);
    }

    public function test_pool_psl_delay(): void
    {
        $this->runPoolDelayTests(
            $this->mockSymfonyClient(new PslDeferred()), 3
        );

        $this->assertTrue(true);
    }

    public function test_pool_react_delay(): void
    {
        $this->runPoolDelayTests(
            $this->mockSymfonyClient(new ReactDeferred()), 3
        );

        $this->assertTrue(true);
    }

    private function runPoolDelayTests(AsyncClientInterface $client, int $totalRequests, int $delay = 1000): void
    {
        $requests = function (int $total) use ($delay) {
            for ($i = 0; $i < $total; $i++) {
                yield function (AsyncClientInterface&Delayable $client) use ($delay): ResponseInterface {
                    $client->delay($delay);

                    return $client->sendRequest($this->requestFactory->createRequest('GET', 'http://localhost'));
                };
            }
        };

        $pool = PoolFactory::createFromClient($client);
        $pool->send($requests($totalRequests));
    }
}
