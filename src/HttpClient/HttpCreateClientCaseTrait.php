<?php

namespace Asynit\HttpClient;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Asynit\Assert\AssertWebCaseTrait;
use Asynit\Attribute\OnCreate;
use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\AddPathPlugin;
use Http\Client\Common\Plugin\HeaderAppendPlugin;
use Http\Client\Common\PluginClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait HttpCreateClientCaseTrait
{
    use AssertWebCaseTrait;

    protected function getBaseUri(): string
    {
        return '';
    }

    protected function isAllowedSelfSignedCertificate(): bool
    {
        return false;
    }

    protected function createHttpClient($baseHeaders = []): ClientInterface
    {
        $tlsContext = new ClientTlsContext('');

        if ($this->isAllowedSelfSignedCertificate()) {
            $tlsContext = $tlsContext->withoutPeerVerification();
        }

        $connectContext = new ConnectContext('');
        $connectContext = $connectContext->withTlsContext($tlsContext);

        $builder = new HttpClientBuilder();
        $builder = $builder->usingPool(new UnlimitedConnectionPool(new DefaultConnectionFactory(null, $connectContext)));
        $client = $builder->build();
        $factory = new Psr17Factory();

        $psrClient = new AmpPsrHttpClient($client, $factory, $factory);

        if ($this->getBaseUri() !== '') {
            $uri = $factory->createUri($this->getBaseUri());
            $plugins = [new AddHostPlugin($uri)];

            if ($baseHeaders !== []) {
                $plugins[] = new HeaderAppendPlugin($baseHeaders);
            }

            if ($uri->getPath() !== '') {
                $plugins[] = new AddPathPlugin($uri);
            }

            $psrClient = new PluginClient($psrClient, $plugins);
        }

        return $psrClient;
    }
}
