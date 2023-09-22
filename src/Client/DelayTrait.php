<?php

declare(strict_types=1);

namespace Fansipan\Peak\Client;

trait DelayTrait
{
    /**
     * @var int<0, max>
     */
    private int $delay = 0;

    public function delay(int $milliseconds): void
    {
        $this->delay = $milliseconds;
    }

    private function getDelay(bool $asSeconds = false): float|int
    {
        if ($this->delay <= 0) {
            return 0;
        }

        $delay = $asSeconds ? $this->delay / 1000 : $this->delay;

        // Reset the delay value to zero
        $this->delay = 0;

        return $delay;
    }
}
