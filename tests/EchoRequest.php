<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Request;

final class EchoRequest extends Request
{
    /**
     * @var string
     */
    private $method;

    public function __construct(string $method = 'get')
    {
        $this->method = mb_strtolower($method);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function endpoint(): string
    {
        return $this->method;
    }
}
