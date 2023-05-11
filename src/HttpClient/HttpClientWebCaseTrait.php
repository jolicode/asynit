<?php

namespace Asynit\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait HttpClientWebCaseTrait
{
    use HttpClientCaseTrait;

    final protected function get(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('GET', $uri, $headers, $body, $version));
    }

    final protected function post(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('POST', $uri, $headers, $body, $version));
    }

    final protected function patch(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('PATCH', $uri, $headers, $body, $version));
    }

    final protected function put(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('PUT', $uri, $headers, $body, $version));
    }

    final protected function delete(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('DELETE', $uri, $headers, $body, $version));
    }

    final protected function options(string $uri, array $headers = [], $body = null, ?string $version = null): ResponseInterface
    {
        return $this->sendRequest($this->createRequest('OPTIONS', $uri, $headers, $body, $version));
    }

    private function createRequest(string $method, string $uri, array $headers = [], $body = null, ?string $version = null): RequestInterface
    {
        $request = $this->httpFactory->createRequest($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (null !== $body) {
            $body = $this->httpFactory->createStream($body);

            $request = $request->withBody($body);
        }

        if (null !== $version) {
            $request = $request->withProtocolVersion($version);
        }

        return $request;
    }
}
