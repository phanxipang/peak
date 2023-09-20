<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool\Client;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Jenky\Atlas\Pool\Concurrency\PslDeferred;
use Jenky\Atlas\Pool\Concurrency\ReactDeferred;
use Jenky\Atlas\Pool\Exception\UnsupportedClientException;
use Jenky\Atlas\Pool\Exception\UnsupportedFeatureException;
use Jenky\Atlas\Pool\Util;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Factory
{
    /**
     * Create new async version of the given client.
     *
     * @throws \Jenky\Atlas\Pool\Exception\UnsupportedClientException
     * @throws \Jenky\Atlas\Pool\Exception\UnsupportedFeatureException
     */
    public static function createAsyncClient(ClientInterface $client): AsyncClientInterface
    {
        if (Util::isPslInstalled()) {
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

        if (Util::isReactInstalled()) {
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

        throw new UnsupportedFeatureException('You cannot use the concurrent request pool feature as the required packages are not installed.');
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
