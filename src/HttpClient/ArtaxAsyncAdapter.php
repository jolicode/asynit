<?php

namespace Asynit\HttpClient;


use Amp\Artax;
use Amp\CancellationTokenSource;
use Http\Adapter\Artax\Internal\ResponseStream;
use Http\Client\Exception\RequestException;
use Http\Client\HttpAsyncClient;
use Http\Message\ResponseFactory;
use Psr\Http\Message\RequestInterface;

class ArtaxAsyncAdapter implements HttpAsyncClient
{
    private $client;

    private $responseFactory;

    public function __construct(ResponseFactory $responseFactory, Artax\Client $client = null)
    {
        if (null === $client) {
            $client = new Artax\DefaultClient();
        }

        $this->client = $client;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        return new PromiseAdapter(\Amp\call(function () use ($request) {
            $cancellationTokenSource = new CancellationTokenSource();

            $req = new \Amp\Artax\Request($request->getUri(), $request->getMethod());
            $req = $req->withProtocolVersions([$request->getProtocolVersion()]);
            $req = $req->withHeaders($request->getHeaders());
            $req = $req->withBody((string) $request->getBody());

            try {
                /** @var Artax\Response $response */
                $response = yield $this->client->request($req, [
                    Artax\Client::OP_MAX_REDIRECTS => 0,
                ], $cancellationTokenSource->getToken());
            } catch (Artax\HttpException $e) {
                throw new RequestException($e->getMessage(), $request, $e);
            }

            return $this->responseFactory->createResponse(
                $response->getStatus(),
                $response->getReason(),
                $response->getHeaders(),
                new ResponseStream($response->getBody()->getInputStream(), $cancellationTokenSource),
                $response->getProtocolVersion()
            );
        }));
    }
}