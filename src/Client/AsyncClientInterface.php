<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Client;

use Jenky\Atlas\Pool\Concurrency\Driver;
use Psr\Http\Client\ClientInterface;

interface AsyncClientInterface extends ClientInterface
{
    /**
     * Get the underlying async driver type.
     */
    public function driver(): ?Driver;
}
