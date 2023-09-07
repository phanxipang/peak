<?php

declare(strict_types=1);

namespace Jenky\Atlas\Pool;

use Clue\React\Mq\Queue;
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
        if (! empty(self::$candidates)) {
            return;
        }

        self::$candidates[] = fn (ConnectorInterface $connector) => $this->createPoolByClientType($connector);

        if ($this->isPslInstalled()) {
            self::$candidates[] = fn (ConnectorInterface $connector) => $this->createPslPool($connector);
        }

        if ($this->isReactInstalled()) {
            self::$candidates[] = fn (ConnectorInterface $connector) => $this->createReactPool($connector);
        }
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
            } catch (\Throwable $e) {
                if ($e instanceof UnsupportedClientException) {
                    throw $e;
                }

                continue;
            }
        }

        throw new UnsupportedFeatureException('You cannot use the pool feature as the required packages are not installed.');
    }

    /**
     * @throws \LogicException
     */
    private function assertConnector(ConnectorInterface $connector): void
    {
        if (! \method_exists($connector, 'withClient')) {
            // @codeCoverageIgnoreStart
            throw new \LogicException('Unable to swap the underlying client of connector '.get_debug_type($connector));
            // @codeCoverageIgnoreEnd
        }
    }

    private function getUnderlyingSymfonyHttpClient(Psr18Client $client): ?HttpClientInterface
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

    private function createPoolByClientType(ConnectorInterface $connector): PoolInterface
    {
        $client = $connector->client();

        return match (true) {
            $this->isReactInstalled() && $client instanceof React\AsyncClientInterface => $this->createReactPool($connector),
            $this->isPslInstalled() && $client instanceof Psl\AsyncClientInterface => $this->createPslPool($connector),
            default => throw new \Exception('Unsupported client. Swap client and retry')
        };
    }

    private function isReactInstalled(): bool
    {
        return \function_exists('React\\Async\\async') && \class_exists(Queue::class);
    }

    private function isPslInstalled(): bool
    {
        return \class_exists(Awaitable::class);
    }

    private function createReactPool(ConnectorInterface $connector): React\Pool
    {
        $client = $connector->client();

        if ($client instanceof React\AsyncClientInterface) {
            return new React\Pool($connector);
        }

        $this->assertConnector($connector);

        if ($client instanceof Psr18Client) {
            $newClient = new React\SymfonyClient($this->getUnderlyingSymfonyHttpClient($client));
        } elseif ($client instanceof ClientInterface) {
            $newClient = new React\GuzzleClient($client);
        } elseif (\class_exists(Browser::class)) {
            $newClient = new React\Client();
        } else {
            // @codeCoverageIgnoreStart
            throw new UnsupportedClientException(\sprintf(
                'The concurrent requests feature cannot be used as the client %s is not supported. To utilize this feature, please install package "react/http".',
                \get_debug_type($client)
            ));
            // @codeCoverageIgnoreEnd
        }

        return new React\Pool($connector->withClient($newClient)); //@phpstan-ignore-line
    }

    private function createPslPool(ConnectorInterface $connector): Psl\Pool
    {
        $client = $connector->client();

        if ($client instanceof Psl\AsyncClientInterface) {
            return new Psl\Pool($connector);
        }

        $this->assertConnector($connector);

        if ($client instanceof Psr18Client) {
            $newClient = new Psl\SymfonyClient($this->getUnderlyingSymfonyHttpClient($client));
        } elseif ($client instanceof ClientInterface) {
            $newClient = new Psl\GuzzleClient($client);
        } else {
            throw new UnsupportedClientException(\sprintf(
                'The client %s is not supported. The PSL Pool only supports "guzzlehttp/guzzle" and "symfony/http-client".',
                \get_debug_type($client)
            ));
        }

        return new Psl\Pool($connector->withClient($newClient)); //@phpstan-ignore-line
    }
}
