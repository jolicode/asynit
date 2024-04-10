<?php

namespace Asynit\HttpClient;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Http\HttpResponse;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Asynit\Assert\AssertWebCaseTrait;
use Asynit\Attribute\OnCreate;

trait HttpClientCaseTrait
{
    use AssertWebCaseTrait;

    private HttpClient|null $httpClient = null;

    protected $allowSelfSignedCertificate = false;

    protected function createHttpClient(bool $allowSelfSignedCertificate = false): HttpClient
    {
        $tlsContext = new ClientTlsContext('');

        if ($allowSelfSignedCertificate) {
            $tlsContext = $tlsContext->withoutPeerVerification();
        }

        $connectContext = new ConnectContext('');
        $connectContext = $connectContext->withTlsContext($tlsContext);

        $builder = new HttpClientBuilder();
        $builder = $builder->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(null, $connectContext)));

        return $builder->build();
    }

    #[OnCreate]
    final public function setUpHttpClient(): void
    {
        $this->httpClient = $this->createHttpClient($this->allowSelfSignedCertificate);
    }

    /**
     * Allow to test a rejection or a resolution of an async call.
     */
    final protected function sendRequest(Request $request): HttpResponse
    {
        return $this->httpClient->request($request);
    }
}
