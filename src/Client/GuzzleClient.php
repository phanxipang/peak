<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Client;

use Fansipan\Concurrent\Concurrency\Deferrable;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class GuzzleClient implements AsyncClientInterface
{
    use AsyncClientTrait;

    private ClientInterface $client;

    public function __construct(
        private readonly Deferrable $deferred,
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

        return $this->deferred->defer(static function (\Closure $resolve, \Closure $reject) use ($promise) {
            $promise->then(
                fn (ResponseInterface $response) => $resolve($response),
                fn (\Throwable $e) => $reject($e)
            )->wait();
        });
    }

    private function getDeferrable(): Deferrable
    {
        return $this->deferred;
    }
}
