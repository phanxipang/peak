<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Connector;

final class EchoConnector extends Connector
{
    public function baseUri(): ?string
    {
        return 'https://postman-echo.com/';
    }
}
