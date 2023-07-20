<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\React;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Jenky\Atlas\Pool\AsyncClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use React\Async;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

final class GuzzleClient implements AsyncClientInterface
{
    private ClientInterface $client;

    private LoopInterface $loop;

    public function __construct(
        ?ClientInterface $client = null,
        ?LoopInterface $loop = null
    ) {
        $this->client = $client ?? new Client();
        $this->loop = $loop ?? Loop::get();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $promise = $this->client->sendAsync($request, [
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::HTTP_ERRORS => false,
        ]);

        $defer = new Deferred();

        $this->loop->futureTick(static function () use ($defer, $promise) {
            $promise->then(
                fn (ResponseInterface $response) => $defer->resolve($response),
                fn (\Throwable $e) => $defer->reject($e)
            )->wait();
        });

        return Async\await($defer->promise());
    }
}
