<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests\Bench;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;

trait BenchTrait
{
    public function benchGuzzlePool(): void
    {
        // Pool::batch(new Client(), $this->cre)
    }
}
