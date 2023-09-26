<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests;

use Jenky\Atlas\Util;

trait TestRequestTrait
{
    protected function createRequests(int $total): iterable
    {
        for ($i = 1; $i <= $total; $i++) {
            yield new AkamaiTileRequest($i);
        }
    }

    protected function createPsrRequests(int $total): iterable
    {
        for ($i = 1; $i <= $total; $i++) {
            yield Util::request(new AkamaiTileRequest($i));
        }
    }
}
