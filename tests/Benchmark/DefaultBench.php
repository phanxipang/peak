<?php

declare(strict_types=1);

namespace Fansipan\Peak\Tests\Benchmark;

use Fansipan\Peak\Tests\TestRequestTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\RequestOptions;

final class DefaultBench
{
    use TestRequestTrait;

    public function benchGuzzlePool(): void
    {
        $options = [
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::HTTP_ERRORS => false,
        ];

        Pool::batch(new Client($options), $this->createPsrRequests(100));
    }
}