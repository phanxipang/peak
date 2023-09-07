<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Psl;

use Http\Discovery\Psr17FactoryDiscovery;
use Jenky\Atlas\Pool\SymfonyClientTrait;
use Psl\Async;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\ResetInterface;

final class SymfonyClient implements AsyncClientInterface, ResetInterface
{
    use SymfonyClientTrait;

    private HttpClientInterface $client;

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ?HttpClientInterface $client = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $this->client = $client ?? HttpClient::create();
        $this->responseFactory = $responseFactory ?? Psr17FactoryDiscovery::findResponseFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * @param \Closure(): ResponseInterface $response
     */
    private function createResponse(\Closure $response): mixed
    {
        $defer = new Async\Deferred();

        Async\Scheduler::defer(static function () use ($defer, $response) {
            $defer->complete($response());
        });

        return $defer->getAwaitable()->await();
    }
}
