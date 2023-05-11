<?php

namespace Asynit\Tests;

use Asynit\Attribute\TestCase;
use Asynit\HttpClient\ApiResponse;
use Asynit\HttpClient\HttpClientApiCaseTrait;
use Asynit\HttpClient\HttpClientWebCaseTrait;
use Psr\Http\Message\ResponseInterface;

#[TestCase]
class WebAndApiTests
{
    use HttpClientWebCaseTrait, HttpClientApiCaseTrait {
        HttpClientWebCaseTrait::get insteadof HttpClientApiCaseTrait;
        HttpClientWebCaseTrait::post insteadof HttpClientApiCaseTrait;
        HttpClientWebCaseTrait::patch insteadof HttpClientApiCaseTrait;
        HttpClientWebCaseTrait::put insteadof HttpClientApiCaseTrait;
        HttpClientWebCaseTrait::options insteadof HttpClientApiCaseTrait;
        HttpClientWebCaseTrait::delete insteadof HttpClientApiCaseTrait;
        HttpClientApiCaseTrait::get as private getApi;
        HttpClientApiCaseTrait::post as private postApi;
        HttpClientApiCaseTrait::patch as patchApi;
        HttpClientApiCaseTrait::put as putApi;
        HttpClientApiCaseTrait::options as optionsApi;
        HttpClientApiCaseTrait::delete as deleteApi;
    }

    public function testJsonWeb()
    {
        $response = $this->get($this->createUri('/get'));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertStatusCode(200, $response);

        $content = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayNotHasKey('Content-Type', $content['headers']);

    }

    public function testJsonApi()
    {
        $response = $this->getApi($this->createUri('/get'));

        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertStatusCode(200, $response);
        $this->assertSame('application/json', $response['headers']['Content-Type']);
    }

    protected function createUri(string $uri): string
    {
        return 'http://127.0.0.1:8081'.$uri;
    }
}
