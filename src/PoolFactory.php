<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Exceptions\UnsupportedException;

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
            return new React\Pool($connector); //@phpstan-ignore-line
        }

        throw new UnsupportedException('You cannot use the pool feature as the "jenky/atlas-react-pool" package is not installed.');
    }
}
