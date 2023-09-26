<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

use Amp\Future;
use Amp\Pipeline\Pipeline;
use Clue\React\Mq\Queue;
use Psl\Async\Awaitable;

final class DriverDiscovery
{
    private static ?Driver $cached = null;

    private static ?Driver $preferred = null;

    /**
     * Find the appropriate async driver based on the installed packages.
     *
     * @throws \RuntimeException
     */
    public static function find(bool $cacheResult = true): Driver
    {
        if (self::$preferred !== null) {
            return self::$preferred;
        }

        if ($cacheResult && self::$cached !== null) {
            return self::$cached;
        }

        if (self::isAmpInstalled()) {
            $driver = Driver::AMP;
        } elseif (self::isPslInstalled()) {
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
        $check = match ($driver) {
            Driver::AMP => self::isAmpInstalled(),
            Driver::PSL => self::isPslInstalled(),
            Driver::REACT => self::isReactInstalled(),
        };

        if (! $check) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(\sprintf(
                'You cannot use the driver %s as required packages are not installed. Try running "composer require %s"',
                $driver->name,
                $driver->value
            ));
            // @codeCoverageIgnoreEnd
        }

        self::$preferred = $driver;
    }

    public static function isAmpInstalled(): bool
    {
        return \class_exists(Future::class) && \class_exists(Pipeline::class);
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
