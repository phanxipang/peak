<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Exception;

final class InvalidPoolRequestException extends \InvalidArgumentException
{
    public function __construct(
        string $request,
        string $response,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = sprintf('Each value of the iterator must be a %s or a \Closure that returns a %s object.', $request, $response);

        parent::__construct($message, $code, $previous);
    }
}
