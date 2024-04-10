<?php

namespace Asynit\HttpClient;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Asynit\Assert\AssertWebCaseTrait;
use Asynit\Attribute\HttpClientConfiguration;
use Asynit\Attribute\OnCreate;
use Asynit\Test;

trait HttpClientCaseTrait
{
    use AssertWebCaseTrait;

    private ?HttpClient $httpClient = null;

    private ?\Closure $configureRequest = null;

    protected $allowSelfSignedCertificate = false;

    protected function createHttpClient(bool $allowSelfSignedCertificate = false, HttpClientConfiguration $httpClientConfiguration = new HttpClientConfiguration()): HttpClient
    {
        $tlsContext = new ClientTlsContext('');

        if ($allowSelfSignedCertificate) {
            $tlsContext = $tlsContext->withoutPeerVerification();
        }

        $connectContext = new ConnectContext('');
        $connectContext = $connectContext->withTlsContext($tlsContext);

        $builder = new HttpClientBuilder();
        $builder = $builder->retry($httpClientConfiguration->retry);
        $builder = $builder->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(null, $connectContext)));

        return $builder->build();
    }

    #[OnCreate]
    final public function setUpHttpClient(): void
    {
        $reflection = new \ReflectionClass($this);

        $httpClientConfiguration = $reflection->getAttributes(HttpClientConfiguration::class);

        if (!$httpClientConfiguration) {
            $httpClientConfiguration = new HttpClientConfiguration();
        } else {
            $httpClientConfiguration = $httpClientConfiguration[0]->newInstance();
        }

        $this->httpClient = $this->createHttpClient($this->allowSelfSignedCertificate, $httpClientConfiguration);

        $this->configureRequest = function (Request $request) use ($httpClientConfiguration) {
            $request->setInactivityTimeout($httpClientConfiguration->timeout);
            $request->setTcpConnectTimeout($httpClientConfiguration->timeout);
            $request->setTlsHandshakeTimeout($httpClientConfiguration->timeout);
            $request->setTransferTimeout($httpClientConfiguration->timeout);
        };
    }

    /**
     * Allow to test a rejection or a resolution of an async call.
     */
    final protected function sendRequest(Request $request): Response
    {
        if (null !== $this->configureRequest) {
            $configureRequest = $this->configureRequest;
            $configureRequest($request);
        }

        return $this->httpClient->request($request);
    }
}
