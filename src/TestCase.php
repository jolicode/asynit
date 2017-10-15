<?php

namespace Asynit;

use Amp\Artax\Client;
use Amp\Artax\DefaultClient;
use Amp\Artax\Response;
use Amp\Parallel\Sync\Lock;
use Amp\Parallel\Sync\Semaphore;
use Amp\Promise;
use Asynit\Assert\AssertWebCaseTrait;
use Asynit\HttpClient\ArtaxAsyncAdapter;
use Http\Client\HttpAsyncClient;
use Http\Message\MessageFactory;
use Psr\Http\Message\RequestInterface;

class TestCase
{
    use AssertWebCaseTrait;

    /** @var MessageFactory */
    private $messageFactory;

    /** @var Semaphore */
    private $semaphore;

    /** @var HttpAsyncClient */
    private $client;

    final public function __construct(MessageFactory $messageFactory, Semaphore $semaphore, Test $test)
    {
        $this->messageFactory = $messageFactory;
        $this->semaphore = $semaphore;
        $this->test = $test;
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
    public function setUp(HttpAsyncClient $asyncClient): HttpAsyncClient
    {
        return $asyncClient;
    }

    final public function initialize()
    {
        $this->client = $this->setUp(new ArtaxAsyncAdapter($this->messageFactory, new DefaultClient()));
    }

    /**
     * Allow to test a rejection or a resolution of an async call.
     *
     * @param RequestInterface $request
     *
     * @return Promise
     */
    final protected function sendRequest(RequestInterface $request): Promise
    {
        return \Amp\call(function () use($request) {
            /** @var Lock $lock */
            $lock = yield $this->semaphore->acquire();
            $response = yield $this->client->sendAsyncRequest($request);

            $lock->release();

            return $response;
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
    final protected function get($uri, $headers = [], $body = null, $version = '1.1'): Promise
    {
        return $this->sendRequest($this->messageFactory->createRequest('GET', $uri, $headers, $body, $version));
    }
    /**
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @return Promise
     */
    final protected function post($uri, $headers = [], $body = null, $version = '1.1'): Promise
    {
        return $this->sendRequest($this->messageFactory->createRequest('POST', $uri, $headers, $body, $version));
    }

    /**
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @return Promise
     */
    final protected function patch($uri, $headers = [], $body = null, $version = '1.1'): Promise
    {
        return $this->sendRequest($this->messageFactory->createRequest('PATCH', $uri, $headers, $body, $version));
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
        return $this->sendRequest($this->messageFactory->createRequest('PUT', $uri, $headers, $body, $version));
    }

    /**
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @return Promise
     */
    final protected function delete($uri, $headers = [], $body = null, $version = '1.1'): Promise
    {
        return $this->sendRequest($this->messageFactory->createRequest('DELETE', $uri, $headers, $body, $version));
    }

    /**
     * @param        $uri
     * @param array  $headers
     * @param null   $body
     * @param string $version
     *
     * @return Promise
     */
    final protected function options($uri, $headers = [], $body = null, $version = '1.1'): Promise
    {
        return $this->sendRequest($this->messageFactory->createRequest('OPTIONS', $uri, $headers, $body, $version));
    }
}
