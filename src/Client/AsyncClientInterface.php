<?php

declare(strict_types=1);

namespace Fansipan\Peak\Client;

use Fansipan\Peak\Concurrency\Driver;
use Psr\Http\Client\ClientInterface;

interface AsyncClientInterface extends ClientInterface
{
    /**
     * Delay the sending of the request in milliseconds. The value must be reset
     * after the request is sent, regardless of the response status.
     *
     * @param  int<0, max> $milliseconds
     */
    public function delay(int $milliseconds): void;

    /**
     * Get the underlying async driver type.
     */
    public function driver(): ?Driver;
}
