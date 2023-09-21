<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Concurrency;

use Clue\React\Mq\Queue;
use Psl\Async\Awaitable;

final class DriverDiscovery
{
    private static ?Driver $cached = null;

    private static ?Driver $prefer = null;

    /**
     * Find the appropriate async driver based on the installed package.
     *
     * @throws \RuntimeException
     */
    public static function find(bool $cacheResult = true): Driver
    {
        if (self::$prefer !== null) {
            return self::$prefer;
        }

        if ($cacheResult && self::$cached !== null) {
            return self::$cached;
        }

        if (self::isPslInstalled()) {
            $driver = Driver::PSL;
        } elseif (self::isReactInstalled()) {
            $driver = Driver::REACT;
        } else {
            throw new \RuntimeException('Unable to find async driver.');
        }

        if ($cacheResult) {
            self::$cached = $driver;
        }

        return $driver;
    }

    /**
     * Set the preferred async driver.
     */
    public static function prefer(Driver $driver): void
    {
        self::$prefer = $driver;
    }

    public static function isReactInstalled(): bool
    {
        return \function_exists('React\\Async\\async') && \class_exists(Queue::class);
    }

    public static function isPslInstalled(): bool
    {
        return \class_exists(Awaitable::class);
    }
}
