<?php

namespace Asynit\HttpClient;

use Psr\Http\Message\RequestInterface;

trait HttpClientApiCaseTrait
{
    use HttpClientCaseTrait;

    protected function getApiContentType(): string
    {
        return 'application/json';
    }

    final protected function get(string $uri, array|null $json = null, array $headers = [], ?string $version = null): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createRequest('GET', $uri, $headers, $json, $version)));
    }

    final protected function post(string $uri, array|null $json = null, array $headers = [], ?string $version = null): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createRequest('POST', $uri, $headers, $json, $version)));
    }

    final protected function patch(string $uri, array|null $json = null, array $headers = [], ?string $version = null): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createRequest('PATCH', $uri, $headers, $json, $version)));
    }

    final protected function put(string $uri, array|null $json = null, array $headers = [], ?string $version = null): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createRequest('PUT', $uri, $headers, $json, $version)));
    }

    final protected function delete(string $uri, array|null $json = null, array $headers = [], ?string $version = null): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createRequest('DELETE', $uri, $headers, $json, $version)));
    }

    final protected function options(string $uri, array|null $json = null, array $headers = [], ?string $version = null): ApiResponse
    {
        return new ApiResponse($this->sendRequest($this->createRequest('OPTIONS', $uri, $headers, $json, $version)));
    }

    private function createRequest(string $method, string $uri, array $headers = [], array|null $json = null, ?string $version = null): RequestInterface
    {
        $request = $this->httpFactory->createRequest($method, $uri);
        $request = $request->withHeader('Content-Type', $this->getApiContentType());
        $request = $request->withHeader('Accept', $this->getApiContentType());

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (null !== $json) {
            $body = $this->httpFactory->createStream(json_encode($json, flags: JSON_THROW_ON_ERROR));

            $request = $request->withBody($body);
        }

        if (null !== $version) {
            $request = $request->withProtocolVersion($version);
        }

        return $request;
    }
}
