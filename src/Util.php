<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Clue\React\Mq\Queue;
use Psl\Async\Awaitable;

final class Util
{
    public static function isReactInstalled(): bool
    {
        return \function_exists('React\\Async\\async') && \class_exists(Queue::class);
    }

    public static function isPslInstalled(): bool
    {
        return \class_exists(Awaitable::class);
    }
}
