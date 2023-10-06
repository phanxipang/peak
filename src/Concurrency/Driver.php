<?php

declare(strict_types=1);

namespace Fansipan\Peak\Concurrency;

enum Driver: string
{
    case AMP = 'amphp/pipeline';
    case PSL = 'azjezz/psl';
    case REACT = 'react/async clue/mq-react';
}
