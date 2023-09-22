<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests;

use Jenky\Atlas\Request;

final class DummyRequest extends Request
{
    public function __construct(
        private string $uri = 'https://example.com',
        private string $method = 'GET'
    ) {
    }

    public function method(): string
    {
        return $this->method;
    }

    public function endpoint(): string
    {
        return $this->uri;
    }
}
