<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Connector;
use Jenky\Atlas\Contracts\PoolableInterface;
use Jenky\Atlas\Traits\Poolable;

final class EchoConnector extends Connector implements PoolableInterface
{
    use Poolable;

    public function baseUri(): ?string
    {
        return 'https://postman-echo.com/';
    }
}
