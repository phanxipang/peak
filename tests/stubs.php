<?php

namespace Jenky\Atlas\Pool\React {

    use GuzzleHttp\ClientInterface;
    use Http\Discovery\Psr17FactoryDiscovery;
    use Jenky\Atlas\Contracts\ConnectorInterface;
    use Jenky\Atlas\Pool\AsyncClientInterface;
    use Jenky\Atlas\Pool\PoolInterface;
    use Jenky\Atlas\Pool\PoolTrait;
    use Psr\Http\Message\RequestInterface;
    use Psr\Http\Message\ResponseInterface;
    use Symfony\Component\HttpClient\HttpClient;
    use Symfony\Contracts\HttpClient\HttpClientInterface;

    class Pool implements PoolInterface
    {
        use PoolTrait;

        public function __construct(public ConnectorInterface $connector)
        {
        }

        public function send(iterable $requests): array
        {
            return [];
        }
    }

    class Client implements AsyncClientInterface
    {
        public function sendRequest(RequestInterface $request): ResponseInterface
        {
            return Psr17FactoryDiscovery::findResponseFactory()->createResponse();
        }
    }

    class SymfonyClient extends Client
    {
        public HttpClientInterface $client;

        public function __construct(?HttpClientInterface $client = null)
        {
            $this->client = $client ?? HttpClient::create();
        }
    }

    class GuzzleClient extends Client
    {
        public ClientInterface $client;

        public function __construct(?ClientInterface $client = null)
        {
            $this->client = $client ?? new \GuzzleHttp\Client();
        }
    }

}
