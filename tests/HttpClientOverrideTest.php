<?php

namespace Asynit\Tests;

use Asynit\TestCase;
use Http\Client\Common\Plugin\BaseUriPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpAsyncClient;
use Http\Message\UriFactory\GuzzleUriFactory;
use Psr\Http\Message\ResponseInterface;

class HttpClientOverrideTest extends TestCase
{
    public function setUp(HttpAsyncClient $asyncClient): HttpAsyncClient
    {
        $uri = (new GuzzleUriFactory())->createUri('http://127.0.0.1:8081');

        return new PluginClient($asyncClient, [
            new BaseUriPlugin($uri)
        ]);
    }

    public function testFoo2()
    {
        $response = yield $this->get('/delay/3');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
