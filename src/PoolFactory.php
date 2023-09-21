<?php

declare(strict_types=1);

namespace Fansipan\Concurrent;

use Fansipan\Concurrent\Client\AsyncClientInterface;
use Fansipan\Concurrent\Client\Factory;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Psr\Http\Client\ClientInterface;

class PoolFactory
{
    public static function createForClient(ClientInterface $client): Pool
    {
        return new ClientPool(Factory::createAsyncClient($client));
    }

    public static function createForConnector(ConnectorInterface $connector): Pool
    {
        $client = $connector->client();

        if (! $client instanceof AsyncClientInterface) {
            if (! \method_exists($connector, 'withClient')) {
                // @codeCoverageIgnoreStart
                throw new \LogicException('Unable to swap the underlying client of connector '.\get_debug_type($connector));
                // @codeCoverageIgnoreEnd
            }

            $connector = $connector->withClient(Factory::createAsyncClient($client));
        }

        return new ConnectorPool($connector);
    }
}
