<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use GuzzleHttp\ClientInterface;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Exceptions\UnsupportedException;
use React\Promise\PromiseInterface;

final class PoolFactory
{
    public static function create(ConnectorInterface $connector): PoolInterface
    {
        $client = $connector->client();

        if ($client instanceof ClientInterface) {
            return new Guzzle\Pool($connector);
        }

        if (interface_exists(PromiseInterface::class)) {
            return new React\Pool($connector);
        }

        throw new UnsupportedException('You cannot use the pool feature as the "amphp/parallel" or "react/http", "react/http" package is not installed.');
        // throw new UnsupportedException('Pool feature is not supported for current client '.get_debug_type($client));
    }
}
