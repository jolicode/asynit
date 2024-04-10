<?php

namespace Asynit\HttpClient;

use Amp\Http\Client\Request;

trait HttpClientApiCaseTrait
{
    use HttpClientCaseTrait;

    protected function getApiContentType(): string
    {
        return 'application/json';
    }

    final protected function get(string $uri, array|null $json = null, array $headers = []): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createApiRequest('GET', $uri, $headers, $json)));
    }

    final protected function post(string $uri, array|null $json = null, array $headers = []): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createApiRequest('POST', $uri, $headers, $json)));
    }

    final protected function patch(string $uri, array|null $json = null, array $headers = []): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createApiRequest('PATCH', $uri, $headers, $json)));
    }

    final protected function put(string $uri, array|null $json = null, array $headers = []): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createApiRequest('PUT', $uri, $headers, $json)));
    }

    final protected function delete(string $uri, array|null $json = null, array $headers = []): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createApiRequest('DELETE', $uri, $headers, $json)));
    }

    final protected function options(string $uri, array|null $json = null, array $headers = []): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createApiRequest('OPTIONS', $uri, $headers, $json)));
    }

    private function createApiRequest(string $method, string $uri, array $headers = [], array|null $json = null): Request
    {
        $request = new Request($uri, $method);
        $request->addHeader('Content-Type', $this->getApiContentType());
        $request->addHeader('Accept', $this->getApiContentType());

        foreach ($headers as $name => $value) {
            $request->addHeader($name, $value);
        }

        if (null !== $json) {
            $request->setBody(json_encode($json, flags: JSON_THROW_ON_ERROR));
        }

        return $request;
    }
}
