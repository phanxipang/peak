<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Client;

use Fansipan\Concurrent\Concurrency\Deferrable;
use Http\Discovery\Psr17FactoryDiscovery;
use Jenky\Atlas\Exception\NetworkException;
use Jenky\Atlas\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Response\StreamableInterface;
use Symfony\Component\HttpClient\Response\StreamWrapper;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

final class SymfonyClient implements AsyncClientInterface, ResetInterface
{
    use AsyncClientTrait;
    use DelayTrait;

    private HttpClientInterface $client;

    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        private readonly Deferrable $deferred,
        ?HttpClientInterface $client = null,
        ?ResponseFactoryInterface $responseFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->client = $client ?? HttpClient::create();
        $this->responseFactory = $responseFactory ?? Psr17FactoryDiscovery::findResponseFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            $body = $request->getBody();

            if ($body->isSeekable()) {
                $body->seek(0);
            }

            $options = [
                'headers' => $request->getHeaders(),
                'body' => $body->getContents(),
            ];

            if ('1.0' === $request->getProtocolVersion()) {
                $options['http_version'] = '1.0';
            }

            $response = $this->client->request($request->getMethod(), (string) $request->getUri(), $options);

            return $this->createResponse($response);
            // @codeCoverageIgnoreStart
        } catch (TransportExceptionInterface $e) {
            if ($e instanceof \InvalidArgumentException) {
                throw new RequestException($e->getMessage(), $request, null, $e);
            }

            throw new NetworkException($e->getMessage(), $request, $e);
        }
        // @codeCoverageIgnoreEnd
    }

    private function createResponse(SymfonyResponseInterface $response): mixed
    {
        return $this->deferred->defer(function (\Closure $resolve, \Closure $reject) use ($response) {
            try {
                $resolve($this->convertToPsrResponse($response));
            } catch (\Throwable $e) {
                $reject($e);
            }
        }, $this->getDelay(true));
    }

    private function convertToPsrResponse(SymfonyResponseInterface $response): ResponseInterface
    {
        $psrResponse = $this->responseFactory->createResponse($response->getStatusCode());

        foreach ($response->getHeaders(false) as $name => $values) {
            foreach ($values as $value) {
                try {
                    $psrResponse = $psrResponse->withAddedHeader($name, $value);
                    // @codeCoverageIgnoreStart
                } catch (\InvalidArgumentException) {
                    // ignore invalid header
                }
                // @codeCoverageIgnoreEnd
            }
        }

        $body = $response instanceof StreamableInterface ? $response->toStream(false) : StreamWrapper::createResource($response, $this->client);
        $body = $this->streamFactory->createStreamFromResource($body);

        if ($body->isSeekable()) {
            try {
                $body->seek(0);
            } catch (\Throwable) {
                //
            }
        }

        return $psrResponse->withBody($body);
    }

    private function getDeferrable(): Deferrable
    {
        return $this->deferred;
    }

    /**
     * @codeCoverageIgnore
     */
    public function withOptions(array $options): static
    {
        $clone = clone $this;
        $clone->client = $clone->client->withOptions($options);

        return $clone;
    }

    /**
     * @codeCoverageIgnore
     */
    public function reset(): void
    {
        if ($this->client instanceof ResetInterface) {
            $this->client->reset();
        }
    }
}
