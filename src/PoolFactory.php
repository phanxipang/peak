<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Exceptions\UnsupportedException;
use React\Http\Browser;
use Symfony\Component\HttpClient\Psr18Client;

final class PoolFactory
{
    /**
     * Create a new pool instance for given connector.
     *
     * @throws UnsupportedException
     */
    public static function create(ConnectorInterface $connector): PoolInterface
    {
        if (class_exists(React\Pool::class)) {
            return self::createReactPool($connector);
        }

        throw new UnsupportedException('You cannot use the pool feature as the "jenky/atlas-react-pool" package is not installed.');
    }

    private static function createReactPool(ConnectorInterface $connector): PoolInterface
    {
        $client = $connector->client();

        if ($client instanceof AsyncClientInterface) {
            return new React\Pool(clone $connector);
        }

        if (! method_exists($connector, 'withClient')) {
            throw new \LogicException('Unable to swap the underlying client of connector '.get_debug_type($connector));
        }

        if ($client instanceof Psr18Client) {
            $newClient = new React\SymfonyClient();
        } else {
            $newClient = new React\Client(new Browser());
        }

        return $connector->withClient($newClient);
    }
}
