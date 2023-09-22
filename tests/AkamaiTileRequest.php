<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests;

use Jenky\Atlas\Request;

final class AkamaiTileRequest extends Request
{
    public function __construct(private int $i)
    {
    }

    public function endpoint(): string
    {
        return "https://http2.akamai.com/demo/tile-$this->i.png";
    }
}
