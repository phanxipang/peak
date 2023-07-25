<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\React;

use Http\Discovery\Psr17FactoryDiscovery;
use Jenky\Atlas\Exception\NetworkException;
use Jenky\Atlas\Exception\RequestException;
use Jenky\Atlas\Pool\AsyncClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use React\Async;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Response\StreamableInterface;
use Symfony\Component\HttpClient\Response\StreamWrapper;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

final class SymfonyClient implements AsyncClientInterface, ResetInterface
{
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

            return Async\await($this->createResponse(
                $this->client->request($request->getMethod(), (string) $request->getUri(), $options)
            ));
            // @codeCoverageIgnoreStart
        } catch (TransportExceptionInterface $e) {
            if ($e instanceof \InvalidArgumentException) {
                throw new RequestException($e->getMessage(), $request, null, $e);
            }

            throw new NetworkException($e->getMessage(), $request, $e);
        }
        // @codeCoverageIgnoreEnd
    }

    private function createResponse(SymfonyResponseInterface $response): PromiseInterface
    {
        $defer = new Deferred();

        $this->loop->futureTick(function () use ($defer, $response) {
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
                } catch (\Throwable $e) {
                    // $defer->reject($e);
                }
            }

            $defer->resolve($psrResponse->withBody($body));
        });

        return $defer->promise();
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
