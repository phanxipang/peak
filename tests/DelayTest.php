<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Tests;

use Fansipan\Concurrent\Client\AsyncClientInterface;
use Fansipan\Concurrent\Client\GuzzleClient;
use Fansipan\Concurrent\Client\ReactClient;
use Fansipan\Concurrent\Client\SymfonyClient;
use Fansipan\Concurrent\Concurrency\Deferrable;
use Fansipan\Concurrent\Concurrency\PslDeferred;
use Fansipan\Concurrent\Concurrency\ReactDeferred;
use Fansipan\Concurrent\PoolFactory;
use Fansipan\Mock\MockResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use Http\Discovery\Psr17FactoryDiscovery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\MockHttpClient;

final class DelayTest extends TestCase
{
    private RequestFactoryInterface $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
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
                yield function (AsyncClientInterface $client) use ($delay): ResponseInterface {
                    $client->delay($delay);

                    return $client->sendRequest($this->requestFactory->createRequest('GET', 'http://localhost'));
                };
            }
        };

        $pool = PoolFactory::createForClient($client);
        $pool->send($requests($totalRequests));
    }

    private function mockGuzzleClient(Deferrable $defer, ?array $response = null): GuzzleClient
    {
        $handler = new MockHandler($response);
        $handlerStack = HandlerStack::create($handler);

        return new GuzzleClient($defer, new Client(['handler' => $handlerStack]));
    }

    private function mockSymfonyClient(Deferrable $defer, mixed $response = null): SymfonyClient
    {
        return new SymfonyClient($defer, new MockHttpClient($response));
    }
}
