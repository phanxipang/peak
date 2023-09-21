<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Tests;

use Fansipan\Concurrent\Client\AsyncClientInterface;
use Fansipan\Concurrent\ClientPool;
use Fansipan\Concurrent\ConnectorPool;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\GenericConnector;
use Jenky\Atlas\Middleware\Interceptor;
use Jenky\Atlas\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

abstract class TestCase extends BaseTestCase
{
    use TestRequestTrait;

    protected function createConnector(?ClientInterface $client = null): ConnectorInterface
    {
        $connector = new GenericConnector();

        return $client ? $connector->withClient($client) : $connector;
    }

    protected function performClientTests(AsyncClientInterface $client): void
    {
        $total = (int) getenv('TEST_TOTAL_CONCURRENT_REQUESTS') ?: 100;

        $responses = (new ClientPool($client))
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

        $responses = (new ConnectorPool($connector))
            ->send($this->createRequests($total));

        $this->assertCount($total, $responses);
        $this->assertInstanceOf(Response::class, $responses[0]);
        $this->assertSame('bar', $responses[0]->header('X-Foo'));
    }
}
