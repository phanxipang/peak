<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests\Benchmark;

use Fansipan\Peak\Client\GuzzleClient;
use Fansipan\Peak\Client\SymfonyClient;
use Fansipan\Peak\Concurrency\AmpDeferred;
use Fansipan\Peak\PoolFactory;
use Fansipan\Peak\Tests\TestTrait;
use PhpBench\Attributes\ParamProviders;

final class AmpBench
{
    use BenchTrait;
    use TestTrait;

    #[ParamProviders(['provideLimits'])]
    public function benchAmpWithGuzzle(array $params): void
    {
        PoolFactory::createFromClient(
            new GuzzleClient(new AmpDeferred())
        )->send($this->createPsrRequests($params['limit']));
    }

    #[ParamProviders(['provideLimits'])]
    public function benchAmpWithSymfony(array $params): void
    {
        PoolFactory::createFromClient(
            new SymfonyClient(new AmpDeferred())
        )->send($this->createPsrRequests($params['limit']));
    }
}
