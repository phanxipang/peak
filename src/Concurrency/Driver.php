<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Concurrency;

enum Driver
{
    case PSL;
    case REACT;
}
