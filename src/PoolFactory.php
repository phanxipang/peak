<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Exceptions\UnsupportedException;

final class PoolFactory
{
    /**
     * @var array<callable(ConnectorInterface): PoolInterface>
     */
    private static array $candidates = [];

    /**
     * @var array<class-string, PoolInterface>
     */
    private static array $cached = [];

    /**
     * Create a new pool instance for given connector.
     *
     * @throws UnsupportedException
     */
    public static function create(ConnectorInterface $connector): PoolInterface
    {
        $key = get_class($connector);

        if (! empty(self::$cached[$key])) {
            return self::$cached[$key];
        }

        foreach (self::$candidates as $callback) {
            try {
                $pool = $callback($connector);

                if ($pool instanceof PoolInterface) {
                    return self::$cached[$key] ??= $pool;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        throw new UnsupportedException('You cannot use the pool feature as the "jenky/atlas-react-pool" package is not installed.');
    }

    /**
     * Register a custom factory.
     *
     * @param  callable(ConnectorInterface): PoolInterface $factory
     */
    public static function register(callable $factory): void
    {
        self::$candidates[] = $factory;
    }
}
