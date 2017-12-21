<?php

class HttpClientOverrideTest extends \Asynit\TestCase
{
    public function setUp(\Http\Client\HttpAsyncClient $asyncClient): \Http\Client\HttpAsyncClient
    {
        $uri = (new \Http\Message\UriFactory\GuzzleUriFactory())->createUri('http://httpbin.org');

        return new \Http\Client\Common\PluginClient($asyncClient, [
            new \Http\Client\Common\Plugin\BaseUriPlugin($uri),
        ]);
    }

    public function testFoo2()
    {
        yield $this->get('/delay/3');
    }

    public function testFoo3()
    {
        yield $this->get('/delay/3');
    }

    public function testFoo4()
    {
        yield $this->get('/delay/3');
    }

    public function testFoo5()
    {
        yield $this->get('/delay/3');
    }
}
