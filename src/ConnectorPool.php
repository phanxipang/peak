<?php

declare(strict_types=1);

namespace Fansipan\Peak;

use Fansipan\Contracts\ConnectorInterface;
use Fansipan\Peak\Client\AsyncClientInterface;
use Fansipan\Peak\Exception\InvalidPoolRequestException;
use Fansipan\Peak\Exception\UnsupportedClientException;
use Fansipan\Request;
use Fansipan\Response;

if (! \interface_exists(ConnectorInterface::class)) {
    // @codeCoverageIgnoreStart
    throw new \LogicException('You cannot use the ConnectorPool as the "fansipan/fansipan" package is not installed.');
    // @codeCoverageIgnoreEnd
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
            /** @var array-key $key */
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

        return $this->createWorker($this->client)->run(
            $promises($this->connector, $requests)
        );
    }
}
