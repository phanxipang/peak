<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests\Benchmark;

use Fansipan\Peak\Tests\TestRequestTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\RequestOptions;
use PhpBench\Attributes\ParamProviders;

final class DefaultBench
{
    use BenchTrait;
    use TestRequestTrait;

    #[ParamProviders(['provideLimits'])]
    public function benchGuzzlePool(array $params): void
    {
        $options = [
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::HTTP_ERRORS => false,
        ];

        Pool::batch(new Client($options), $this->createPsrRequests($params['limit']));
    }
}
