<?php

declare(strict_types=1);

namespace Fansipan\Peak\Client;

interface Delayable
{
    /**
     * Delay the sending of the request in milliseconds. The value must be reset
     * after the request is sent, regardless of the response status.
     *
     * @param  int<0, max> $milliseconds
     */
    public function delay(int $milliseconds): void;
}
