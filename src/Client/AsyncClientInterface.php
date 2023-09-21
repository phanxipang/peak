<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Client;

use Fansipan\Concurrent\Concurrency\Driver;
use Psr\Http\Client\ClientInterface;

interface AsyncClientInterface extends ClientInterface
{
    /**
     * Get the underlying async driver type.
     */
    public function driver(): ?Driver;
}
