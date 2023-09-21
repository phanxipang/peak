<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Concurrency;

enum Driver
{
    case PSL;
    case REACT;
}
