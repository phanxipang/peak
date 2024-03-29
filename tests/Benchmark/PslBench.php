<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests\Benchmark;

use Fansipan\Peak\Client\GuzzleClient;
use Fansipan\Peak\Client\SymfonyClient;
use Fansipan\Peak\Concurrency\PslDeferred;
use Fansipan\Peak\PoolFactory;
use Fansipan\Peak\Tests\TestTrait;
use PhpBench\Attributes\ParamProviders;

final class PslBench
{
    use BenchTrait;
    use TestTrait;

    #[ParamProviders(['provideLimits'])]
    public function benchPslWithGuzzle(array $params): void
    {
        PoolFactory::createFromClient(
            new GuzzleClient(new PslDeferred())
        )->send($this->createPsrRequests($params['limit']));
    }

    #[ParamProviders(['provideLimits'])]
    public function benchPslWithSymfony(array $params): void
    {
        PoolFactory::createFromClient(
            new SymfonyClient(new PslDeferred())
        )->send($this->createPsrRequests($params['limit']));
    }
}
