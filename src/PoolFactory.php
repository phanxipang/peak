<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Client\AsyncClientInterface;
use Jenky\Atlas\Pool\Client\Factory;
use Psr\Http\Client\ClientInterface;

class PoolFactory
{
    public static function createForClient(ClientInterface $client): PoolInterface
    {
        return new Pool(Factory::createAsyncClient($client));
    }

    public static function createForConnector(ConnectorInterface $connector): PoolInterface
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
