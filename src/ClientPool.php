<?php

declare(strict_types=1);

namespace Fansipan\Peak;

use Fansipan\Peak\Client\AsyncClientInterface;
use Fansipan\Peak\Exception\InvalidPoolRequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @implements Pool<RequestInterface|callable(AsyncClientInterface): ResponseInterface, ResponseInterface>
 */
final class ClientPool implements Pool
{
    use PoolTrait;

    public function __construct(private readonly AsyncClientInterface $client)
    {
    }

    public function send(iterable $requests): array
    {
        $promises = static function (AsyncClientInterface $client, iterable $requests) {
            /** @var array-key $key */
            foreach ($requests as $key => $request) {
                if ($request instanceof RequestInterface) {
                    yield $key => static fn (): ResponseInterface => $client->sendRequest($request);
                } elseif (\is_callable($request)) {
                    yield $key => static fn (): ResponseInterface => $request($client);
                } else {
                    throw new InvalidPoolRequestException(RequestInterface::class, ResponseInterface::class);
                }
            }
        };

        return $this->createWorker($this->client)->run(
            $promises($this->client, $requests)
        );
    }
}
