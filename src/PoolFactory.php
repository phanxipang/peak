<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use GuzzleHttp\ClientInterface;
use Jenky\Atlas\Contracts\ConnectorInterface;
use Jenky\Atlas\Pool\Exception\UnsupportedClientException;
use Jenky\Atlas\Pool\Exception\UnsupportedFeatureException;
use Jenky\Concurrency\PoolInterface;
use Psl\Async\Awaitable;
use React\Http\Browser;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PoolFactory
{
    /**
     * @var array<callable(ConnectorInterface): PoolInterface>
     */
    private static array $candidates = [];

    public function __construct()
    {
        $this->boot();
    }

    /**
     * Create a new pool instance for given connector.
     *
     * @throws UnsupportedClientException
     * @throws UnsupportedFeatureException
     */
    public static function create(ConnectorInterface $connector): PoolInterface
    {
        return (new self())->createPool($connector);
    }

    /**
     * Create a new pool instance for given connector.
     *
     * @throws UnsupportedClientException
     * @throws UnsupportedFeatureException
     */
    public function createPool(ConnectorInterface $connector): PoolInterface
    {
        foreach (self::$candidates as $callback) {
            try {
                return $callback($connector);
            } catch (\Throwable) {
                continue;
            }
        }

        throw new UnsupportedFeatureException('You cannot use the pool feature as the required package is not installed.');
    }

    private function boot(): void
    {
        if (! empty(self::$candidates)) {
            return;
        }

        if ($this->isPslInstalled()) {
            self::$candidates[] = fn (ConnectorInterface $connector) => $this->createPslPool($connector);
        }

        if ($this->isReactInstalled()) {
            self::$candidates[] = fn (ConnectorInterface $connector) => $this->createReactPool($connector);
        }
    }

    /**
     * @throws \LogicException
     */
    private function assertConnector(ConnectorInterface $connector): void
    {
        if (! method_exists($connector, 'withClient')) {
            // @codeCoverageIgnoreStart
            throw new \LogicException('Unable to swap the underlying client of connector '.get_debug_type($connector));
            // @codeCoverageIgnoreEnd
        }
    }

    private function getUnderlyingSymfonyHttpClient(Psr18Client $client): HttpClientInterface
    {
        $reflectionProperty = new \ReflectionProperty($client, 'client');
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($client);
    }

    private function isReactInstalled(): bool
    {
        return function_exists('React\\Async\\async');
    }

    private function isPslInstalled(): bool
    {
        return class_exists(Awaitable::class);
    }

    private function createReactPool(ConnectorInterface $connector): React\Pool
    {
        $client = $connector->client();

        if ($client instanceof AsyncClientInterface) {
            return new React\Pool(clone $connector);
        }

        $this->assertConnector($connector);

        if ($client instanceof Psr18Client) {
            try {
                $newClient = new React\SymfonyClient($this->getUnderlyingSymfonyHttpClient($client));
            } catch (\Throwable) {
                $newClient = new React\SymfonyClient();
            }
        } elseif ($client instanceof ClientInterface) {
            $newClient = new React\GuzzleClient($client);
        } elseif (class_exists(Browser::class)) {
            $newClient = new React\Client();
        } else {
            throw new UnsupportedClientException(sprintf(
                'The concurrent requests feature cannot be used as the client %s is not supported. To utilize this feature, please install package "react/http".',
                get_debug_type($client)
            ));
        }

        return new React\Pool($connector->withClient($newClient)); //@phpstan-ignore-line
    }

    private function createPslPool(ConnectorInterface $connector): Psl\Pool
    {
        $client = $connector->client();

        if ($client instanceof AsyncClientInterface) {
            return new Psl\Pool(clone $connector);
        }

        $this->assertConnector($connector);

        if ($client instanceof Psr18Client) {
            try {
                $newClient = new Psl\SymfonyClient($this->getUnderlyingSymfonyHttpClient($client));
            } catch (\Throwable) {
                $newClient = new Psl\SymfonyClient();
            }
        } elseif ($client instanceof ClientInterface) {
            $newClient = new Psl\GuzzleClient($client);
        } else {
            throw new UnsupportedClientException(sprintf('The client %s is not supported.', get_debug_type($client)));
        }

        return new Psl\Pool($connector->withClient($newClient)); //@phpstan-ignore-line
    }
}
