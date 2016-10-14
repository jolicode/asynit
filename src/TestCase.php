<?php

namespace Asynit;

use Asynit\Runner\FutureHttp;
use Asynit\Runner\FutureHttpPool;
use Http\Client\HttpAsyncClient;
use Http\Message\RequestFactory;
use Psr\Http\Message\RequestInterface;

class TestCase
{
    /** @var RequestFactory */
    private $requestFactory;

    /** @var FutureHttpPool */
    private $futureHttpPool;

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
     * @param HttpAsyncClient $asyncClient
     *
     * @return HttpAsyncClient
     */
    public function setUp(HttpAsyncClient $asyncClient)
    {
        return $asyncClient;
    }

    /**
     * Allow to test a rejection or a resolution of an async call.
     *
     * @param RequestInterface $requestInterface
     *
     * @return FutureHttp
     */
    final protected function sendRequest(RequestInterface $requestInterface)
    {
        $runner = new FutureHttp($requestInterface);
        $this->futureHttpPool->add($runner);

        return $runner;
    }

    /**
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @return FutureHttp
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
     * @return FutureHttp
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
     * @return FutureHttp
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
     * @return FutureHttp
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
     * @return FutureHttp
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
     * @return FutureHttp
     */
    final protected function options($uri, $headers = [], $body = null, $version = '1.1')
    {
        return $this->sendRequest($this->requestFactory->createRequest('OPTIONS', $uri, $headers, $body, $version));
    }
}
