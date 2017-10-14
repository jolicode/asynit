<?php

namespace Asynit;

use Amp\Artax\Client;
use Amp\Artax\DefaultClient;
use Amp\Artax\Response;
use Amp\Parallel\Sync\Lock;
use Amp\Parallel\Sync\Semaphore;
use Amp\Promise;
use Asynit\Assert\AssertWebCaseTrait;
use Http\Message\MessageFactory;
use Psr\Http\Message\RequestInterface;

class TestCase
{
    use AssertWebCaseTrait;

    /** @var MessageFactory */
    private $messageFactory;

    /** @var Semaphore */
    private $semaphore;

    /** @var Client */
    private $client;

    /** @var Test $test */
    private $test;

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
     * @param Client $asyncClient
     *
     * @return Client
     */
    public function setUp(Client $asyncClient): Client
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
     * @param RequestInterface $request
     *
     * @return Promise
     */
    final protected function sendRequest(RequestInterface $request): Promise
    {
        return \Amp\call(function () use($request) {
            /** @var Lock $lock */
            $lock = yield $this->semaphore->acquire();

            $req = new \Amp\Artax\Request($request->getUri(), $request->getMethod());
            $req = $req->withProtocolVersions([$request->getProtocolVersion()]);
            $req = $req->withHeaders($request->getHeaders());
            $req = $req->withBody((string) $request->getBody());
            /** @var Response $response */
            $response = yield $this->client->request($req);
            $content = yield $response->getBody();

            $lock->release();

            return $this->messageFactory->createResponse(
                $response->getStatus(),
                $response->getReason(),
                $response->getHeaders(),
                $content,
                $response->getProtocolVersion()
            );
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
