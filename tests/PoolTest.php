<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Tests;

use Fansipan\Concurrent\Client\Factory;
use Fansipan\Concurrent\Client\GuzzleClient;
use Fansipan\Concurrent\Client\ReactClient;
use Fansipan\Concurrent\Client\SymfonyClient;
use Fansipan\Concurrent\Concurrency\Driver;
use Fansipan\Concurrent\Concurrency\DriverDiscovery;
use Fansipan\Concurrent\ConnectorPool;
use Fansipan\Concurrent\Exception\InvalidPoolRequestException;
use Fansipan\Concurrent\Exception\UnsupportedClientException;
use Fansipan\Concurrent\Pool;
use Fansipan\Concurrent\PoolFactory;
use Fansipan\Concurrent\PoolTrait;
use GuzzleHttp\Client;
use Http\Discovery\Psr17FactoryDiscovery;
use Jenky\Atlas\GenericConnector;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\Psr18Client;

final class PoolTest extends TestCase
{
    public function test_concurrency_limit(): void
    {
        $pool = new NullPool();

        $pool->concurrent(10);

        $this->assertNotSame($pool, $pool->concurrent(10), 'Pool is immutable');

        $this->expectException(\ValueError::class);

        $pool->concurrent(-1);
    }

    public function test_async_client_factory(): void
    {
        $client = Factory::createAsyncClient(new Client());
        $this->assertInstanceOf(GuzzleClient::class, $client);

        $client = Factory::createAsyncClient(new Psr18Client());
        $this->assertInstanceOf(SymfonyClient::class, $client);
    }

    public function test_invalid_pool_request(): void
    {
        $client = new ReactClient();

        $clientPool = PoolFactory::createForClient($client);

        $this->expectException(InvalidPoolRequestException::class);

        $clientPool->send([1, 2, 3]);

        $connectorPool = PoolFactory::createForConnector(
            (new GenericConnector())->withClient($client)
        );

        $this->expectException(InvalidPoolRequestException::class);
        $connectorPool->send([1, fn () => new \stdClass()]);
    }

    public function test_create_pool_using_unsupported_client(): void
    {
        $pool = PoolFactory::createForConnector((new GenericConnector())->withClient(new Client()));
        $this->assertInstanceOf(GuzzleClient::class, $this->getClientFromPool($pool));

        $pool = PoolFactory::createForConnector((new GenericConnector())->withClient(new Psr18Client()));
        $this->assertInstanceOf(SymfonyClient::class, $this->getClientFromPool($pool));

        $this->expectException(UnsupportedClientException::class);
        $pool = PoolFactory::createForClient(new FakeHttpClient());

        DriverDiscovery::prefer(Driver::REACT);
        $pool = PoolFactory::createForClient(Factory::createAsyncClient(new FakeHttpClient()));
        $this->assertInstanceOf(ReactClient::class, $this->getClientFromPool($pool));
    }

    private function getClientFromPool(Pool $pool): ClientInterface
    {
        if ($pool instanceof ConnectorPool) {
            $reflection = new \ReflectionProperty($pool, 'connector');
            $reflection->setAccessible(true);

            return $reflection->getValue($pool)->client();
        }

        $reflection = new \ReflectionProperty($pool, 'client');
        $reflection->setAccessible(true);

        return $reflection->getValue($pool);
    }
}

final class NullPool implements Pool
{
    use PoolTrait;

    public function send(iterable $requests): array
    {
        return iterator_to_array($requests);
    }
}

final class FakeHttpClient implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return Psr17FactoryDiscovery::findResponseFactory()->createResponse();
    }
}
