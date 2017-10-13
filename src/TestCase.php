<?php

namespace Asynit;

use Amp\Artax\Client;
use Amp\Artax\DefaultClient;
use Amp\Loop;
use Amp\Promise;
use Asynit\Assert\AssertWebCaseTrait;
use Asynit\Runner\FutureHttp;
use Asynit\Runner\FutureHttpPool;
use Http\Message\RequestFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TestCase
{
    use AssertWebCaseTrait;

    /** @var RequestFactory */
    private $requestFactory;

    /** @var FutureHttpPool */
    private $futureHttpPool;

    /** @var Client */
    private $client;

    final public function __construct(RequestFactory $requestFactory, FutureHttpPool $pool)
    {
        $this->requestFactory = $requestFactory;
        $this->futureHttpPool = $pool;
    }

    /**
     * @return FutureHttpPool
     */
    public function getFutureHttpPool()
    {
        return $this->futureHttpPool;
    }

    /**
     * Run before each test.
     *
     * Allow to set default services and context, and also decorate the http async client.
     *
     * @param Client $asyncClient
     *
     * @return Client
     */
    public function setUp(Client $asyncClient)
    {
        return $asyncClient;
    }

    final public function initialize()
    {
        $this->client = $this->setUp(new DefaultClient());
    }

    /**
     * Allow to test a rejection or a resolution of an async call.
     *
     * @param RequestInterface $requestInterface
     *
     * @return Promise
     */
    final protected function sendRequest(RequestInterface $request)
    {
        return \Amp\call(function () use($request) {
            yield $this->client->request($request);
        });
    }

    /**
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @return Promise
     */
    final protected function get($uri, $headers = [], $body = null, $version = '1.1')
    {
        return $this->sendRequest($this->requestFactory->createRequest('GET', $uri, $headers, $body, $version));
    }
    /**
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @return Promise
     */
    final protected function post($uri, $headers = [], $body = null, $version = '1.1')
    {
        return $this->sendRequest($this->requestFactory->createRequest('POST', $uri, $headers, $body, $version));
    }

    /**
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @return Promise
     */
    final protected function patch($uri, $headers = [], $body = null, $version = '1.1')
    {
        return $this->sendRequest($this->requestFactory->createRequest('PATCH', $uri, $headers, $body, $version));
    }

    /**
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @return Promise
     */
    final protected function put($uri, $headers = [], $body = null, $version = '1.1')
    {
        return $this->sendRequest($this->requestFactory->createRequest('PUT', $uri, $headers, $body, $version));
    }

    /**
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @return Promise
     */
    final protected function delete($uri, $headers = [], $body = null, $version = '1.1')
    {
        return $this->sendRequest($this->requestFactory->createRequest('DELETE', $uri, $headers, $body, $version));
    }

    /**
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @return Promise
     */
    final protected function options($uri, $headers = [], $body = null, $version = '1.1')
    {
        return $this->sendRequest($this->requestFactory->createRequest('OPTIONS', $uri, $headers, $body, $version));
    }
}
