<?php

declare(strict_types=1);

namespace Fansipan\Concurrent;

use Fansipan\Concurrent\Client\AsyncClientInterface;
use Fansipan\Concurrent\Exception\InvalidPoolRequestException;
use Fansipan\Concurrent\Exception\UnsupportedClientException;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;

if (! \interface_exists(ConnectorInterface::class)) {
    throw new \LogicException('You cannot use the ConnectorPool as the "fansipan/fansipan" package is not installed.');
}

/**
 * @implements Pool<Request|callable(ConnectorInterface): Response, Response>
 */
final class ConnectorPool implements Pool
{
    use PoolTrait;

    private AsyncClientInterface $client;

    public function __construct(private readonly ConnectorInterface $connector)
    {
        $client = $connector->client();

        if (! $client instanceof AsyncClientInterface) {
            // @codeCoverageIgnoreStart
            throw new UnsupportedClientException(\sprintf(
                'The client %s is not supported. Please swap the underlying client to supported one.',
                \get_debug_type($client)
            ));
            // @codeCoverageIgnoreEnd
        }

        $this->client = $client;
    }

    public function send(iterable $requests): array
    {
        $promises = static function (ConnectorInterface $connector, iterable $requests) {
            foreach ($requests as $key => $request) {
                if ($request instanceof Request) {
                    yield $key => static fn (): Response => $connector->send($request);
                } elseif (\is_callable($request)) {
                    yield $key => static fn (): Response => $request($connector);
                } else {
                    throw new InvalidPoolRequestException(Request::class, Response::class);
                }
            }
        };

        return $this->getRunner($this->client)->run($promises($this->connector, $requests));
    }
}
