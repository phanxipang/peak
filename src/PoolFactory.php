<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use GuzzleHttp\ClientInterface;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Exceptions\UnsupportedException;
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

    /**
     * @codeCoverageIgnore
     */
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
            try {
                $reflectionProperty = new \ReflectionProperty($client, 'client');
                $reflectionProperty->setAccessible(true);

                $newClient = new React\SymfonyClient($reflectionProperty->getValue($client));
            } catch (\Throwable) {
                $newClient = new React\SymfonyClient();
            }
        } elseif ($client instanceof ClientInterface) {
            $newClient = new React\GuzzleClient($client);
        } else {
            $newClient = new React\Client();
        }

        return new React\Pool($connector->withClient($newClient));
    }
}
