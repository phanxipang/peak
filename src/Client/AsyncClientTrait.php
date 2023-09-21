<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Client;

use Jenky\Atlas\Pool\Concurrency\Deferrable;
use Jenky\Atlas\Pool\Concurrency\Driver;
use Jenky\Atlas\Pool\Concurrency\PslDeferred;
use Jenky\Atlas\Pool\Concurrency\ReactDeferred;

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
