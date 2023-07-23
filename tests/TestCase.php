<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Middleware\Interceptor;
use Jenky\Atlas\NullConnector;
use Jenky\Atlas\Response;
use Jenky\Concurrency\PoolInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

abstract class TestCase extends BaseTestCase
{
    abstract protected function createPool(ConnectorInterface $connector): PoolInterface;

    protected function createRequests(int $total): iterable
    {
        for ($i=1; $i <= $total; $i++) {
            yield new AkamaiTileRequest($i);
        }
    }

    protected function createConnector(?ClientInterface $client = null): ConnectorInterface
    {
        $connector = new NullConnector();

        return $client ? $connector->withClient($client) : $connector;
    }

    protected function performTests(ConnectorInterface $connector, int $totalRequests = 100): void
    {
        $total = (int) getenv('TEST_TOTAL_CONCURRENT_REQUESTS') ?: $totalRequests;

        $connector->middleware()->push(
            Interceptor::response(function (ResponseInterface $response) {
                return $response->withHeader('X-Foo', 'bar');
            })
        );

        $responses = $this->createPool($connector)
            ->send($this->createRequests($total));

        $this->assertCount($total, $responses);
        $this->assertInstanceOf(Response::class, $responses[0]);
        $this->assertSame('bar', $responses[0]->header('X-Foo'));
    }
}
