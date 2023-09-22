<?php

declare(strict_types=1);

namespace Fansipan\Concurrent\Client;

use Fansipan\Concurrent\Concurrency\Driver;
use Fansipan\Concurrent\Concurrency\DriverDiscovery;
use Fansipan\Concurrent\Concurrency\PslDeferred;
use Fansipan\Concurrent\Concurrency\ReactDeferred;
use Fansipan\Concurrent\Exception\UnsupportedClientException;
use Fansipan\Concurrent\Exception\UnsupportedFeatureException;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Factory
{
    /**
     * Create new async version of the given client.
     *
     * @throws \Fansipan\Concurrent\Exception\UnsupportedClientException
     * @throws \Fansipan\Concurrent\Exception\UnsupportedFeatureException
     */
    public static function createAsyncClient(ClientInterface $client): AsyncClientInterface
    {
        if ($client instanceof AsyncClientInterface) {
            return $client;
        }

        $driver = DriverDiscovery::find();

        if ($driver === Driver::PSL) {
            if ($client instanceof GuzzleClientInterface) {
                return new GuzzleClient(new PslDeferred(), $client);
            }

            if ($client instanceof Psr18Client) {
                return new SymfonyClient(new PslDeferred(), self::getUnderlyingSymfonyHttpClient($client));
            }

            throw new UnsupportedClientException(\sprintf(
                'The client %s is not supported. The PSL Pool only supports "guzzlehttp/guzzle" and "symfony/http-client".',
                \get_debug_type($client)
            ));
        }

        if ($driver === Driver::REACT) {
            if ($client instanceof GuzzleClientInterface) {
                return new GuzzleClient(new ReactDeferred(), $client);
            }

            if ($client instanceof Psr18Client) {
                return new SymfonyClient(new ReactDeferred(), self::getUnderlyingSymfonyHttpClient($client));
            }

            if (\class_exists(Browser::class)) {
                return new ReactClient();
            }

            throw new UnsupportedClientException(\sprintf(
                'The concurrent requests feature cannot be used as the client %s is not supported. To utilize this feature, please install package "react/http".',
                \get_debug_type($client)
            ));
        }

        // @codeCoverageIgnoreStart
        throw new UnsupportedFeatureException('You cannot use the concurrent request pool feature as the required packages are not installed.'); // @phpstan-ignore-line
        // @codeCoverageIgnoreEnd
    }

    private static function getUnderlyingSymfonyHttpClient(Psr18Client $client): ?HttpClientInterface
    {
        try {
            $reflectionProperty = new \ReflectionProperty($client, 'client');
            $reflectionProperty->setAccessible(true);

            return $reflectionProperty->getValue($client);
            // @codeCoverageIgnoreStart
        } catch (\Throwable) {
            return null;
        }
        // @codeCoverageIgnoreEnd
    }
}
