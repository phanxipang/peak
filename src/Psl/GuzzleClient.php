<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Psl;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Jenky\Atlas\Pool\AsyncClientInterface;
use Psl\Async;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class GuzzleClient implements AsyncClientInterface
{
    private ClientInterface $client;

    public function __construct(
        ?ClientInterface $client = null,
    ) {
        $this->client = $client ?? new Client();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $promise = $this->client->sendAsync($request, [
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::HTTP_ERRORS => false,
        ]);

        $defer = new Async\Deferred();

        Async\Scheduler::defer(static function () use ($defer, $promise) {
            $promise->then(
                fn (ResponseInterface $response) => $defer->complete($response),
                fn (\Throwable $e) => $defer->error($e)
            )->wait();
        });

        return $defer->getAwaitable()->await();
    }
}
