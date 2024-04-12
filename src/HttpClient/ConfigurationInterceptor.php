<?php

namespace Asynit\HttpClient;

use Amp\Cancellation;
use Amp\Http\Client\ApplicationInterceptor;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Asynit\Attribute\HttpClientConfiguration;

final readonly class ConfigurationInterceptor implements ApplicationInterceptor
{
    public function __construct(private HttpClientConfiguration $configuration)
    {
    }

    public function request(Request $request, Cancellation $cancellation, DelegateHttpClient $httpClient): Response
    {
        $request->setInactivityTimeout($this->configuration->timeout);
        $request->setTcpConnectTimeout($this->configuration->timeout);
        $request->setTlsHandshakeTimeout($this->configuration->timeout);
        $request->setTransferTimeout($this->configuration->timeout);

        return $httpClient->request($request, $cancellation);
    }
}
