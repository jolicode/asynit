<?php

namespace Asynit;

use Asynit\Assert\AssertWebCaseTrait;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Amp\Sync\Semaphore;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class TestCase
{
    use AssertWebCaseTrait;

    final public function __construct(
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private Semaphore $semaphore,
        Test $test,
        protected ClientInterface $client,
    )
    {
        $this->test = $test;
    }

    public function initialize()
    {
        $this->client = $this->setUp($this->client);
    }

    /**
     * Run before each test.
     *
     * Allow to set default services and context, and also decorate the http async client.
     *
     * @return ClientInterface
     */
    public function setUp(ClientInterface $asyncClient): ClientInterface
    {
        return $asyncClient;
    }

    /**
     * Allow to test a rejection or a resolution of an async call.
     */
    final protected function sendRequest(RequestInterface $request): ResponseInterface
    {
        $lock = $this->semaphore->acquire();
        $response = $this->client->sendRequest($request);
        $lock->release();

        return $response;
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

    protected function createUri(string $uri): string
    {
        return $uri;
    }

    private function createRequest(string $method, string $uri, array $headers = [], $body = null, ?string $version = null): RequestInterface {
        $request = $this->requestFactory->createRequest($method, $this->createUri($uri));

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $body = $this->streamFactory->createStream($body);

            $request = $request->withBody($body);
        }

        if ($version !== null) {
            $request = $request->withProtocolVersion($version);
        }

        return $request;
    }
}
