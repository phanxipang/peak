<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Concurrency;

enum Driver: string
{
    case PSL = 'azjezz/psl';
    case REACT = 'react/async react/http';
}
