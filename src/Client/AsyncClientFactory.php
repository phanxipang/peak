<?php

declare(strict_types=1);

namespace Fansipan\Peak\Client;

use Fansipan\Peak\Concurrency\Driver;
use Fansipan\Peak\Concurrency\DriverDiscovery;
use Fansipan\Peak\Concurrency\PslDeferred;
use Fansipan\Peak\Concurrency\ReactDeferred;
use Fansipan\Peak\Exception\UnsupportedClientException;
use Fansipan\Peak\Exception\UnsupportedFeatureException;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AsyncClientFactory
{
    /**
     * Create new async version of the given client.
     *
     * @throws \Fansipan\Peak\Exception\UnsupportedClientException
     * @throws \Fansipan\Peak\Exception\UnsupportedFeatureException
     */
    public static function create(?ClientInterface $client = null): AsyncClientInterface
    {
        if ($client === null) {
            $client = self::createClient();
        }

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

    private static function createClient(): ClientInterface
    {
        if (! class_exists(Psr18ClientDiscovery::class)) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('Unable to create PSR-18 client as the "php-http/discovery" package is not installed. Try running "composer require php-http/discovery".');
            // @codeCoverageIgnoreEnd
        }

        return Psr18ClientDiscovery::find();
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
