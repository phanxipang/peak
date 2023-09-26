<?php

declare(strict_types=1);

namespace Fansipan\Peak;

use Fansipan\Peak\Client\AsyncClientInterface;
use Fansipan\Peak\Concurrency\Driver;
use Fansipan\Peak\Concurrency\PslWorker;
use Fansipan\Peak\Concurrency\ReactWorker;
use Fansipan\Peak\Concurrency\Worker;
use Fansipan\Peak\Exception\UnsupportedFeatureException;

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

    private function createWorker(AsyncClientInterface $client): Worker
    {
        $driver = $client->driver();

        return match (true) {
            $driver === Driver::PSL => new PslWorker($this->concurrency),
            $driver === Driver::REACT => new ReactWorker($this->concurrency),
            default => throw new UnsupportedFeatureException('You cannot use the concurrent request pool feature as the required packages are not installed.'),
        };
    }
}
