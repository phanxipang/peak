<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests\Benchmark;

trait BenchTrait
{
    public function provideLimits(): \Generator
    {
        yield '50 rqs' => ['limit' => 50];
        yield '100 rqs' => ['limit' => 100];
        yield '200 rqs' => ['limit' => 200];
    }
}
