<?php

namespace Asynit\Tests;

use Amp\Http\HttpResponse;
use Asynit\HttpClient\HttpClientWebCaseTrait;

class AnotherTestHttp
{
    use HttpClientWebCaseTrait;

    public function test_from_another_file()
    {
        $response = $this->get('http://127.0.0.1:8081/');

        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertStatusCode(200, $response);

        return __METHOD__;
    }
}
