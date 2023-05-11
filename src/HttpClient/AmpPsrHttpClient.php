<?php

namespace Asynit\HttpClient;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class AmpPsrHttpClient implements ClientInterface
{
    public function __construct(
        private HttpClient $client,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $ampRequest = new Request(
            $request->getUri(),
            $request->getMethod(),
            $request->getBody()->getContents(),
        );

        foreach ($request->getHeaders() as $name => $values) {
            $ampRequest->addHeader($name, $values);
        }

        $ampResponse = $this->client->request($ampRequest);

        $response = $this->responseFactory->createResponse(
            $ampResponse->getStatus(),
        );

        foreach ($ampResponse->getHeaders() as $name => $values) {
            $response = $response->withHeader($name, $values);
        }

        $body = $this->streamFactory->createStream($ampResponse->getBody()->buffer());

        return $response->withBody($body);
    }
}
