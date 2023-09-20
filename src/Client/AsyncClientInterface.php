<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Client;

use Psr\Http\Client\ClientInterface;

interface AsyncClientInterface extends ClientInterface
{
    public const DRIVER_PSL = 1;
    public const DRIVER_REACT = 2;

    /**
     * Get the underlying async driver type.
     */
    public function driver(): int;
}
