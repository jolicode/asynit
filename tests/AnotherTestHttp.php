<?php

namespace Asynit\Tests;

use Asynit\HttpClient\HttpClientWebCaseTrait;
use Psr\Http\Message\ResponseInterface;

class AnotherTestHttp
{
    use HttpClientWebCaseTrait;

    public function test_from_another_file()
    {
        $response = $this->get('http://127.0.0.1:8081/');

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertStatusCode(200, $response);

        return __METHOD__;
    }
}
