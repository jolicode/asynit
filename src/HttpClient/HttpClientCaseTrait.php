<?php

namespace Asynit\HttpClient;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Asynit\Assert\AssertWebCaseTrait;
use Asynit\Attribute\OnCreate;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait HttpClientCaseTrait
{
    use AssertWebCaseTrait;

    private ClientInterface|null $httpClient = null;

    private Psr17Factory|null $httpFactory = null;

    protected $allowSelfSignedCertificate = false;

    protected function createHttpClient(bool $allowSelfSignedCertificate = false): ClientInterface
    {
        $tlsContext = new ClientTlsContext('');

        if ($allowSelfSignedCertificate) {
            $tlsContext = $tlsContext->withoutPeerVerification();
        }

        $connectContext = new ConnectContext('');
        $connectContext = $connectContext->withTlsContext($tlsContext);

        $builder = new HttpClientBuilder();
        $builder = $builder->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(null, $connectContext)));
        $client = $builder->build();
        $factory = new Psr17Factory();

        return new AmpPsrHttpClient($client, $factory, $factory);
    }

    #[OnCreate]
    final public function setUpHttpClient(): void
    {
        $this->httpClient = $this->createHttpClient($this->allowSelfSignedCertificate);
        $this->httpFactory = new Psr17Factory();
    }

    /**
     * Allow to test a rejection or a resolution of an async call.
     */
    final protected function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->sendRequest($request);
    }
}
