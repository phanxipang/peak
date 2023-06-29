<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use GuzzleHttp\ClientInterface;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Exceptions\UnsupportedException;

final class PoolFactory
{
    public static function create(ConnectorInterface $connector): PoolInterface
    {
        $client = $connector->client();

        if ($client instanceof ClientInterface) {
            return new Guzzle\Pool($connector);
        }

        throw new UnsupportedException('Pool feature is not supported for current client '.get_debug_type($client));
    }

    /*
     * Get default pool instance.
     *
     * @return array<class-string, callable>
     */
    /* private static function candidates(): array
    {
        return [
            ReactPool::class => fn (): bool => function_exists('React\\Async\\async')
                && function_exists('React\\Async\\await')
                && function_exists('React\\Async\\parallel'),
            AmpPool::class => fn (): bool => function_exists('Amp\\async')
                && function_exists('Amp\\Future\awaitAll'),
        ];
    } */
}
