<?php

declare(strict_types=1);

namespace Fansipan\Concurrent;

use Fansipan\Concurrent\Client\AsyncClientInterface;
use Fansipan\Concurrent\Concurrency\Driver;
use Fansipan\Concurrent\Concurrency\PslConcurrency;
use Fansipan\Concurrent\Concurrency\ReactConcurrency;
use Fansipan\Concurrent\Concurrency\Runner;
use Fansipan\Concurrent\Exception\UnsupportedFeatureException;

trait PoolTrait
{
    /**
     * @var int<1, max>
     */
    private int $concurrency = 25;

    /**
     * @param  int<1, max> $concurrency
     *
     * @throws \ValueError
     */
    public function concurrent(int $concurrency): Pool
    {
        if ($concurrency < 1) {
            throw new \ValueError('Argument #1 ($concurrency) must be positive, got '.$concurrency);
        }

        $clone = clone $this;
        $clone->concurrency = $concurrency;

        return $clone;
    }

    private function getRunner(AsyncClientInterface $client): Runner
    {
        $driver = $client->driver();

        return match (true) {
            $driver === Driver::PSL => new PslConcurrency($this->concurrency),
            $driver === Driver::REACT => new ReactConcurrency($this->concurrency),
            default => throw new UnsupportedFeatureException('You cannot use the concurrent request pool feature as the required packages are not installed.'),
        };
    }
}
