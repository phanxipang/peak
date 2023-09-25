<?php

declare(strict_types=1);

namespace Fansipan\Peak\Client;

trait DelayTrait
{
    /**
     * The delay in milliseconds.
     *
     * @var int<0, max>
     */
    private int $delay = 0;

    public function delay(int $milliseconds): void
    {
        $this->delay = $milliseconds;
    }

    private function getDelayAsSeconds(): float
    {
        if ($this->delay <= 0) {
            return 0;
        }

        return $this->delay / 1000;
    }
}
