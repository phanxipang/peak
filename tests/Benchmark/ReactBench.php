<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests\Benchmark;

use Fansipan\Peak\Client\GuzzleClient;
use Fansipan\Peak\Client\SymfonyClient;
use Fansipan\Peak\Concurrency\ReactDeferred;
use Fansipan\Peak\PoolFactory;
use Fansipan\Peak\Tests\TestRequestTrait;

final class ReactBench
{
    use TestRequestTrait;

    public function benchReactPoolUsingGuzzle(): void
    {
        PoolFactory::createFromClient(
            new GuzzleClient(new ReactDeferred())
        )->send($this->createPsrRequests(100));
    }

    public function benchReactPoolUsingSymfony(): void
    {
        PoolFactory::createFromClient(
            new SymfonyClient(new ReactDeferred())
        )->send($this->createPsrRequests(100));
    }
}
