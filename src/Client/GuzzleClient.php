<?php

declare(strict_types=1);

namespace Fansipan\Peak\Client;

use Fansipan\Peak\Concurrency\Deferrable;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class GuzzleClient implements AsyncClientInterface
{
    use AsyncClientTrait;
    use DelayTrait;

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
                static fn (ResponseInterface $response) => $resolve($response),
                static fn (\Throwable $e) => $reject($e)
            )->wait();
        }, $this->getDelay(true));
    }

    private function getDeferrable(): Deferrable
    {
        return $this->deferred;
    }
}
