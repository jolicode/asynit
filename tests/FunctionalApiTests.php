<?php

namespace Asynit\Tests;

use Asynit\Attribute\TestCase;
use Asynit\HttpClient\ApiResponse;
use Asynit\HttpClient\HttpClientApiCaseTrait;

#[TestCase]
class FunctionalApiTests
{
    use HttpClientApiCaseTrait;

    public function testJson()
    {
        $response = $this->get($this->createUri('/get'));

        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertStatusCode(200, $response);
        $this->assertSame('application/json', $response['headers']['Content-Type']);
    }

    protected function createUri(string $uri): string
    {
        return 'http://127.0.0.1:8081'.$uri;
    }
}
