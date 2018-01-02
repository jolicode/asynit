<?php

namespace Asynit\Tests;

use Asynit\TestCase;
use GuzzleHttp\Psr7\Request;
use Http\Client\Common\Plugin\BaseUriPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpAsyncClient;
use Http\Message\UriFactory\GuzzleUriFactory;
use Psr\Http\Message\ResponseInterface;

class HttpClientOverrideTestPromise extends TestCase
{
    public function setUp(HttpAsyncClient $asyncClient)
    {
        $uri = (new GuzzleUriFactory())->createUri('http://127.0.0.1:8081');
        $client = new PluginClient($asyncClient, [
            new BaseUriPlugin($uri),
        ]);

        $request = new Request('GET', '/delay/1');

        yield $client->sendAsyncRequest($request);

        return $client;
    }

    public function testFoo2()
    {
        $response = yield $this->get('/delay/3');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
