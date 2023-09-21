<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Client;

use Fansipan\Concurrent\Concurrency\Deferrable;
use Fansipan\Concurrent\Concurrency\Driver;
use Fansipan\Concurrent\Concurrency\PslDeferred;
use Fansipan\Concurrent\Concurrency\ReactDeferred;

trait AsyncClientTrait
{
    abstract private function getDeferrable(): Deferrable;

    public function driver(): ?Driver
    {
        $deferrable = $this->getDeferrable();

        return match (true) {
            $deferrable instanceof PslDeferred => Driver::PSL,
            $deferrable instanceof ReactDeferred => Driver::REACT,
            default => null,
        };
    }
}
