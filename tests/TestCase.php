<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\GenericConnector;
use Jenky\Atlas\Middleware\Interceptor;
use Jenky\Atlas\Pool\Client\AsyncClientInterface;
use Jenky\Atlas\Pool\Pool;
use Jenky\Atlas\Pool\PoolInterface;
use Jenky\Atlas\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

abstract class TestCase extends BaseTestCase
{
    use TestRequestTrait;

    abstract protected function createPoolFromClient(AsyncClientInterface $client): PoolInterface;

    protected function createPool(ConnectorInterface|AsyncClientInterface $client): PoolInterface
    {
        if ($client instanceof AsyncClientInterface) {
            return $this->createPoolFromClient($client);
        }

        return new Pool($client);
    }

    protected function createConnector(?ClientInterface $client = null): ConnectorInterface
    {
        $connector = new GenericConnector();

        return $client ? $connector->withClient($client) : $connector;
    }

    protected function performClientTests(AsyncClientInterface $client): void
    {
        $total = (int) getenv('TEST_TOTAL_CONCURRENT_REQUESTS') ?: 100;

        $responses = $this->createPool($client)
            ->send($this->createPsrRequests($total));

        $this->assertCount($total, $responses);
        $this->assertInstanceOf(ResponseInterface::class, $responses[0]);
    }

    protected function performConnectorTests(ConnectorInterface $connector): void
    {
        $total = (int) getenv('TEST_TOTAL_CONCURRENT_REQUESTS') ?: 100;

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
