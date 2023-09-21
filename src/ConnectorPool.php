<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Client\AsyncClientInterface;
use Jenky\Atlas\Pool\Exception\InvalidPoolRequestException;
use Jenky\Atlas\Pool\Exception\UnsupportedClientException;
use Jenky\Atlas\Request;
use Jenky\Atlas\Response;

/**
 * @implements PoolInterface<Request|callable(ConnectorInterface): Response, Response>
 */
final class ConnectorPool implements PoolInterface
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
