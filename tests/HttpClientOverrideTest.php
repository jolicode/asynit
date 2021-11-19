<?php

namespace Asynit\Tests;

use Asynit\TestCase;
use GuzzleHttp\Psr7\HttpFactory;
use Http\Client\Common\Plugin\AddPathPlugin;
use Http\Client\Common\PluginClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

class HttpClientOverrideTest extends TestCase
{
    public function setUp(ClientInterface $client): ClientInterface
    {
        $uri = (new HttpFactory())->createUri('https://httpbin.org/delay');

        return new PluginClient($client, [
            new AddPathPlugin($uri),
        ]);
    }

    public function testFoo2()
    {
        $response = $this->get('/3');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    protected function createUri(string $uri): string
    {
        return 'https://httpbin.org' . $uri;
    }
}
