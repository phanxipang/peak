<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests\Benchmark;

use Fansipan\Peak\Client\GuzzleClient;
use Fansipan\Peak\Client\ReactClient;
use Fansipan\Peak\Client\SymfonyClient;
use Fansipan\Peak\Concurrency\ReactDeferred;
use Fansipan\Peak\PoolFactory;
use Fansipan\Peak\Tests\TestTrait;
use PhpBench\Attributes\ParamProviders;

final class ReactBench
{
    use BenchTrait;
    use TestTrait;

    #[ParamProviders(['provideLimits'])]
    public function benchReactWithGuzzle(array $params): void
    {
        PoolFactory::createFromClient(
            new GuzzleClient(new ReactDeferred())
        )->send($this->createPsrRequests($params['limit']));
    }

    #[ParamProviders(['provideLimits'])]
    public function benchReactWithSymfony(array $params): void
    {
        PoolFactory::createFromClient(
            new SymfonyClient(new ReactDeferred())
        )->send($this->createPsrRequests($params['limit']));
    }

    #[ParamProviders(['provideLimits'])]
    public function benchReactWithHttpClient(array $params): void
    {
        PoolFactory::createFromClient(new ReactClient())
            ->send($this->createPsrRequests($params['limit']));
    }
}
