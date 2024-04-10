<?php

namespace Asynit\HttpClient;

use Amp\Http\Client\Request;
use Amp\Http\Client\Response;

trait HttpClientWebCaseTrait
{
    use HttpClientCaseTrait;

    final protected function get(string $uri, array $headers = [], $body = null): Response
    {
        return $this->sendRequest($this->createRequest('GET', $uri, $headers, $body));
    }

    final protected function post(string $uri, array $headers = [], $body = null): Response
    {
        return $this->sendRequest($this->createRequest('POST', $uri, $headers, $body));
    }

    final protected function patch(string $uri, array $headers = [], $body = null): Response
    {
        return $this->sendRequest($this->createRequest('PATCH', $uri, $headers, $body));
    }

    final protected function put(string $uri, array $headers = [], $body = null): Response
    {
        return $this->sendRequest($this->createRequest('PUT', $uri, $headers, $body));
    }

    final protected function delete(string $uri, array $headers = [], $body = null): Response
    {
        return $this->sendRequest($this->createRequest('DELETE', $uri, $headers, $body));
    }

    final protected function options(string $uri, array $headers = [], $body = null): Response
    {
        return $this->sendRequest($this->createRequest('OPTIONS', $uri, $headers, $body));
    }

    private function createRequest(string $method, string $uri, array $headers = [], $body = null): Request
    {
        $request = new Request($uri, $method);

        foreach ($headers as $name => $value) {
            $request->addHeader($name, $value);
        }

        if (null !== $body) {
            $request->setBody($body);
        }

        return $request;
    }
}
