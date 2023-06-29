<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Tests;

use Jenky\Atlas\Request;

final class AkamaiTileRequest extends Request
{
    /**
     * @var int
     */
    private $i;

    public function __construct(int $i)
    {
        $this->i = $i;
    }

    public function endpoint(): string
    {
        return "https://http2.akamai.com/demo/tile-$this->i.png";
    }
}
