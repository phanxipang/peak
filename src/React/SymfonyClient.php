<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\React;

use Http\Discovery\Psr17FactoryDiscovery;
use Jenky\Atlas\Pool\SymfonyClientTrait;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use React\Async;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Service\ResetInterface;

final class SymfonyClient implements AsyncClientInterface, ResetInterface
{
    use SymfonyClientTrait;

    private HttpClientInterface $client;

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private LoopInterface $loop;

    public function __construct(
        ?HttpClientInterface $client = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?LoopInterface $loop = null
    ) {
        $this->client = $client ?? HttpClient::create();
        $this->responseFactory = $responseFactory ?? Psr17FactoryDiscovery::findResponseFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->loop = $loop ?? Loop::get();
    }

    /**
     * @param \Closure(): ResponseInterface $response
     */
    private function createResponse(\Closure $response): mixed
    {
        $defer = new Deferred();

        $this->loop->futureTick(static function () use ($defer, $response) {
            $defer->resolve($response());
        });

        return Async\await($defer->promise());
    }
}
