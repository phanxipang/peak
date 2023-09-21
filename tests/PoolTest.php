<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use GuzzleHttp\Client;
use Http\Discovery\Psr17FactoryDiscovery;
use Jenky\Atlas\GenericConnector;
use Jenky\Atlas\Pool\Client\Factory;
use Jenky\Atlas\Pool\Client\GuzzleClient;
use Jenky\Atlas\Pool\Client\SymfonyClient;
use Jenky\Atlas\Pool\Exception\UnsupportedClientException;
use Jenky\Atlas\Pool\PoolFactory;
use Jenky\Atlas\Pool\PoolInterface;
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

    private function getClientFromPool(PoolInterface $pool): ClientInterface
    {
        $reflection = new \ReflectionProperty($pool, 'connector');
        $reflection->setAccessible(true);

        return $reflection->getValue($pool)->client();
    }

    public function test_create_pool_using_unsupported_client(): void
    {
        $pool = PoolFactory::createForConnector((new GenericConnector())->withClient(new Client()));
        $this->assertInstanceOf(GuzzleClient::class, $this->getClientFromPool($pool));

        $pool = PoolFactory::createForConnector((new GenericConnector())->withClient(new Psr18Client()));
        $this->assertInstanceOf(SymfonyClient::class, $this->getClientFromPool($pool));
    }

    // public function test_factory_react(): void
    // {
    //     $factory = $this->createFactory('createReactPool');

    //     $pool = $factory->createPool((new GenericConnector())->withClient(new Psr18Client()));
    //     $this->getClientFromPool(Pool\React\Pool::class, Pool\React\SymfonyClient::class, $pool);

    //     $pool = $factory->createPool((new GenericConnector())->withClient(new Client()));
    //     $this->getClientFromPool(Pool\React\Pool::class, Pool\React\GuzzleClient::class, $pool);

    //     $pool = $factory->createPool((new GenericConnector())->withClient(new FakeHttpClient()));
    //     $this->getClientFromPool(Pool\React\Pool::class, Pool\React\Client::class, $pool);
    // }

    // public function test_factory_psl(): void
    // {
    //     $factory = $this->createFactory('createPslPool');

    //     $pool = $factory->createPool((new GenericConnector())->withClient(new Psr18Client()));
    //     $this->getClientFromPool(Pool\Psl\Pool::class, Pool\Psl\SymfonyClient::class, $pool);

    //     $pool = $factory->createPool((new GenericConnector())->withClient(new Client()));
    //     $this->getClientFromPool(Pool\Psl\Pool::class, Pool\Psl\GuzzleClient::class, $pool);

    //     $this->expectException(UnsupportedClientException::class);
    //     $pool = $factory->createPool((new GenericConnector())->withClient(new FakeHttpClient()));
    // }
}

final class NullPool implements PoolInterface
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
