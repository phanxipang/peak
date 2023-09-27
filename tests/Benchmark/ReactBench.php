<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests\Benchmark;

use Fansipan\Peak\Client\GuzzleClient;
use Fansipan\Peak\Client\ReactClient;
use Fansipan\Peak\Client\SymfonyClient;
use Fansipan\Peak\Concurrency\ReactDeferred;
use Fansipan\Peak\PoolFactory;
use Fansipan\Peak\Tests\TestRequestTrait;
use PhpBench\Attributes\ParamProviders;

final class ReactBench
{
    use BenchTrait;
    use TestRequestTrait;

    #[ParamProviders(['provideLimits'])]
    public function benchReactPoolUsingGuzzle(array $params): void
    {
        PoolFactory::createFromClient(
            new GuzzleClient(new ReactDeferred())
        )->send($this->createPsrRequests($params['limit']));
    }

    #[ParamProviders(['provideLimits'])]
    public function benchReactPoolUsingSymfony(array $params): void
    {
        PoolFactory::createFromClient(
            new SymfonyClient(new ReactDeferred())
        )->send($this->createPsrRequests($params['limit']));
    }

    #[ParamProviders(['provideLimits'])]
    public function benchReactPoolUsingHttpClient(array $params): void
    {
        PoolFactory::createFromClient(new ReactClient())
            ->send($this->createPsrRequests($params['limit']));
    }
}
