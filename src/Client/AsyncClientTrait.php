<?php

declare(strict_types=1);

namespace Fansipan\Peak\Client;

use Fansipan\Peak\Concurrency\Deferrable;
use Fansipan\Peak\Concurrency\Driver;
use Fansipan\Peak\Concurrency\PslDeferred;
use Fansipan\Peak\Concurrency\ReactDeferred;

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
