<?php

namespace Jenky\Atlas\Pool\Tests;

use GuzzleHttp\Client;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\NullConnector;
use Jenky\Atlas\Pool;
use Jenky\Atlas\Pool\Exception\UnsupportedClientException;
use Jenky\Atlas\Pool\Exception\UnsupportedFeatureException;
use Jenky\Atlas\Pool\PoolFactory;
use Jenky\Concurrency\PoolInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Psr18Client;

final class PoolTest extends TestCase
{
    public function test_concurrent(): void
    {
        $pool = PoolFactory::create(new NullConnector());

        $responses = $pool->concurrent(10)
            ->send([
                new DummyRequest(),
                new DummyRequest(),
            ]);

        $this->assertCount(2, $responses);

        $this->expectException(\ValueError::class);

        $pool->concurrent(-1);
    }

    public function test_factory(): void
    {
        $factory = new PoolFactory();

        $reflection = new \ReflectionClass($factory);
        $reflection->setStaticPropertyValue('candidates', []);

        $this->expectException(UnsupportedFeatureException::class);

        $factory->createPool(new NullConnector());
    }

    private function createFactory(string $method): PoolFactory
    {
        $factory = new PoolFactory();

        $reflection = new \ReflectionClass($factory);

        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        $reflection->setStaticPropertyValue('candidates', [fn (ConnectorInterface $connector) => $method->invoke($factory, $connector)]);

        return $factory;
    }

    /**
     * @param class-string $poolClass
     * @param class-string $clientClass
     */
    private function assertPoolAndClient(string $poolClass, string $clientClass, PoolInterface $pool): void
    {
        $this->assertInstanceOf($poolClass, $pool);

        $reflection = new \ReflectionProperty($pool, 'connector');
        $reflection->setAccessible(true);

        $this->assertInstanceOf($clientClass, $reflection->getValue($pool)->client());
    }

    public function test_factory_using_supported_client(): void
    {
        $pool = PoolFactory::create((new NullConnector())->withClient(new Pool\React\GuzzleClient()));
        $this->assertPoolAndClient(Pool\React\Pool::class, Pool\React\GuzzleClient::class, $pool);

        $pool = PoolFactory::create((new NullConnector())->withClient(new Pool\Psl\SymfonyClient()));
        $this->assertPoolAndClient(Pool\Psl\Pool::class, Pool\Psl\SymfonyClient::class, $pool);
    }

    public function test_factory_react(): void
    {
        $factory = $this->createFactory('createReactPool');

        $pool = $factory->createPool((new NullConnector())->withClient(new Psr18Client()));
        $this->assertPoolAndClient(Pool\React\Pool::class, Pool\React\SymfonyClient::class, $pool);

        $pool = $factory->createPool((new NullConnector())->withClient(new Client()));
        $this->assertPoolAndClient(Pool\React\Pool::class, Pool\React\GuzzleClient::class, $pool);

        $pool = $factory->createPool((new NullConnector())->withClient(new FakeHttpClient()));
        $this->assertPoolAndClient(Pool\React\Pool::class, Pool\React\Client::class, $pool);
    }

    public function test_factory_psl(): void
    {
        $factory = $this->createFactory('createPslPool');

        $pool = $factory->createPool((new NullConnector())->withClient(new Psr18Client()));
        $this->assertPoolAndClient(Pool\Psl\Pool::class, Pool\Psl\SymfonyClient::class, $pool);

        $pool = $factory->createPool((new NullConnector())->withClient(new Client()));
        $this->assertPoolAndClient(Pool\Psl\Pool::class, Pool\Psl\GuzzleClient::class, $pool);

        $this->expectException(UnsupportedClientException::class);
        $pool = $factory->createPool((new NullConnector())->withClient(new FakeHttpClient()));
    }
}
