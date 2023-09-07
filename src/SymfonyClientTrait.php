<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Jenky\Atlas\Exception\NetworkException;
use Jenky\Atlas\Pool\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpClient\Response\StreamableInterface;
use Symfony\Component\HttpClient\Response\StreamWrapper;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyResponseInterface;
use Symfony\Contracts\Service\ResetInterface;

trait SymfonyClientTrait
{
    /**
     * @param \Closure(): ResponseInterface $response
     */
    abstract private function createResponse(\Closure $response): mixed;

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

            return $this->createResponse(fn () => $this->convertToPsrResponse($response));
            // @codeCoverageIgnoreStart
        } catch (TransportExceptionInterface $e) {
            if ($e instanceof \InvalidArgumentException) {
                throw new RequestException($e->getMessage(), $request, null, $e);
            }

            throw new NetworkException($e->getMessage(), $request, $e);
        }
        // @codeCoverageIgnoreEnd
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
