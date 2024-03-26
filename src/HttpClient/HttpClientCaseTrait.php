<?php

namespace Asynit\HttpClient;

use Asynit\Attribute\OnCreate;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait HttpClientCaseTrait
{
    use HttpCreateClientCaseTrait;

    private ClientInterface|null $httpClient = null;

    private Psr17Factory|null $httpFactory = null;

    #[OnCreate]
    final public function setUpHttpClient(): void
    {
        $this->httpClient = $this->createHttpClient();
        $this->httpFactory = new Psr17Factory();
    }

    /**
     * Allow to test a rejection or a resolution of an async call.
     */
    final protected function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->sendRequest($request);
    }
}
