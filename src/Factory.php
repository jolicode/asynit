<?php

declare(strict_types=1);

namespace Asynit;

use Asynit\Dns\ResolverFactory;
use Asynit\Output\Chain;
use Asynit\Output\Count;
use Asynit\Output\Detector;
use Asynit\Output\OutputInterface;
use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpAsyncClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Message\UriFactory\GuzzleUriFactory;
use React\EventLoop\LoopInterface;
use React\SocketClient\DnsConnector;
use React\SocketClient\SecureConnector;
use React\SocketClient\TcpConnector;
use React\HttpClient\Client as ReactClient;
use Http\Adapter\React\Client as ReactAdapter;

class Factory
{
    /**
     * @param LoopInterface $loop
     * @param string        $dns
     * @param bool          $allowSelfSigned
     * @param null          $baseHost
     *
     * @return HttpAsyncClient
     */
    public static function createClient(LoopInterface $loop, $dns = '8.8.8.8', $allowSelfSigned = false, $baseHost = null)
    {
        $requestFactory = new GuzzleMessageFactory();
        $uriFactory = new GuzzleUriFactory();
        $dnsResolver = (new ResolverFactory())->createCached($dns, $loop);
        $connector = new DnsConnector(new TcpConnector($loop), $dnsResolver);

        // Build the HTTP Client
        $reactClient = new ReactAdapter($requestFactory, $loop, new ReactClient($connector, new SecureConnector($connector, $loop, [
            'allow_self_signed' => $allowSelfSigned,
        ])));

        $plugins = [
            new ContentLengthPlugin(),
        ];

        if (null !== $baseHost) {
            $plugins[] = new AddHostPlugin($uriFactory->createUri($baseHost));
        }

        return new PluginClient($reactClient, $plugins);
    }

    /**
     * @param LoopInterface $loop
     * @param null          $forceTty
     * @param null          $forceNoTty
     *
     * @return array
     */
    public static function createOutput(LoopInterface $loop, $forceTty = null, $forceNoTty = null)
    {
        $countOutput = new Count();
        $chainOutput = new Chain();
        $chainOutput->addOutput((new Detector($loop))->detect($forceTty, $forceNoTty));
        $chainOutput->addOutput($countOutput);

        return [$chainOutput, $countOutput];
    }
}
