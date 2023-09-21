<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use GuzzleHttp\Client;
use Http\Discovery\Psr17FactoryDiscovery;
use Jenky\Atlas\GenericConnector;
use Jenky\Atlas\Pool\Client\Factory;
use Jenky\Atlas\Pool\Client\GuzzleClient;
use Jenky\Atlas\Pool\Client\ReactClient;
use Jenky\Atlas\Pool\Client\SymfonyClient;
use Jenky\Atlas\Pool\Concurrency\Driver;
use Jenky\Atlas\Pool\Concurrency\DriverDiscovery;
use Jenky\Atlas\Pool\ConnectorPool;
use Jenky\Atlas\Pool\Exception\UnsupportedClientException;
use Jenky\Atlas\Pool\Pool;
use Jenky\Atlas\Pool\PoolFactory;
use Jenky\Atlas\Pool\PoolTrait;
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
