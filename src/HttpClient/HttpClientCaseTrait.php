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

    protected function createHttpClient(HttpClientConfiguration $httpClientConfiguration = new HttpClientConfiguration()): HttpClient
    {
        $tlsContext = new ClientTlsContext('');

        if ($httpClientConfiguration->allowSelfSignedCertificate) {
            $tlsContext = $tlsContext->withoutPeerVerification();
        }

        $connectContext = new ConnectContext('');
        $connectContext = $connectContext->withTlsContext($tlsContext);

        $builder = new HttpClientBuilder();
        $builder = $builder->retry($httpClientConfiguration->retry);
        $builder = $builder->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(null, $connectContext)));
        $builder = $builder->intercept(new ConfigurationInterceptor($httpClientConfiguration));

        return $builder->build();
    }

    #[OnCreate]
    final public function setUpHttpClient(HttpClientConfiguration $httpClientConfiguration): void
    {
        $reflection = new \ReflectionClass($this);

        $httpClientConfigurationAttribute = $reflection->getAttributes(HttpClientConfiguration::class);

        if ($httpClientConfigurationAttribute) {
            $httpClientConfiguration = $httpClientConfigurationAttribute[0]->newInstance();
        }

        $this->httpClient = $this->createHttpClient($httpClientConfiguration);
    }

    /**
     * Allow to test a rejection or a resolution of an async call.
     */
    final protected function sendRequest(Request $request): Response
    {
        return $this->httpClient->request($request);
    }
}
