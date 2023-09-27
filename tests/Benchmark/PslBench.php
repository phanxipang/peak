<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests\Benchmark;

use Fansipan\Peak\Client\GuzzleClient;
use Fansipan\Peak\Client\SymfonyClient;
use Fansipan\Peak\Concurrency\PslDeferred;
use Fansipan\Peak\PoolFactory;
use Fansipan\Peak\Tests\TestRequestTrait;

final class PslBench
{
    use TestRequestTrait;

    public function benchPslPoolUsingGuzzle(): void
    {
        PoolFactory::createFromClient(
            new GuzzleClient(new PslDeferred())
        )->send($this->createPsrRequests(100));
    }

    public function benchPslPoolUsingSymfony(): void
    {
        PoolFactory::createFromClient(
            new SymfonyClient(new PslDeferred())
        )->send($this->createPsrRequests(100));
    }
}
