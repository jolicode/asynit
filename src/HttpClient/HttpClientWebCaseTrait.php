<?php

namespace Asynit\HttpClient;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Asynit\Attribute\OnCreate;
use Asynit\Assert\AssertWebCaseTrait;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait HttpClientWebCaseTrait
{
    use AssertWebCaseTrait;

    private ClientInterface|null $httpClient = null;

    private Psr17Factory|null $httpFactory = null;

    protected $allowSelfSignedCertificate = false;

    protected function createHttpClient(bool $allowSelfSignedCertificate = false): ClientInterface
    {
        $tlsContext = new ClientTlsContext('');

        if ($allowSelfSignedCertificate) {
            $tlsContext = $tlsContext->withoutPeerVerification();
        }

        $connectContext = new ConnectContext('');
        $connectContext = $connectContext->withTlsContext($tlsContext);

        $builder = new HttpClientBuilder();
        $builder = $builder->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(null, $connectContext)));
        $client = $builder->build();
        $factory = new Psr17Factory();

        return new AmpPsrHttpClient($client, $factory, $factory);
    }

    #[OnCreate]
    final public function setUpHttpClient(): void
    {
        $this->httpClient = $this->createHttpClient($this->allowSelfSignedCertificate);
        $this->httpFactory = new Psr17Factory();
    }

    /**
     * Allow to test a rejection or a resolution of an async call.
     */
    final protected function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->sendRequest($request);
    }

    final protected function get(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('GET', $uri, $headers, $body, $version));
    }

    final protected function post(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('POST', $uri, $headers, $body, $version));
    }

    final protected function patch(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('PATCH', $uri, $headers, $body, $version));
    }

    final protected function put(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('PUT', $uri, $headers, $body, $version));
    }

    final protected function delete(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('DELETE', $uri, $headers, $body, $version));
    }

    final protected function options(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('OPTIONS', $uri, $headers, $body, $version));
    }

    private function createRequest(string $method, string $uri, array $headers = [], $body = null, ?string $version = null): RequestInterface
    {
        $request = $this->httpFactory->createRequest($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (null !== $body) {
            $body = $this->httpFactory->createStream($body);

            $request = $request->withBody($body);
        }

        if (null !== $version) {
            $request = $request->withProtocolVersion($version);
        }

        return $request;
    }
}
