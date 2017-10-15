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
     * @param null          $forceTty
     * @param null          $forceNoTty
     *
     * @return array
     */
    public static function createOutput($forceTty = null, $forceNoTty = null)
    {
        $countOutput = new Count();
        $chainOutput = new Chain();
        $chainOutput->addOutput((new Detector())->detect($forceTty, $forceNoTty));
        $chainOutput->addOutput($countOutput);

        return [$chainOutput, $countOutput];
    }
}
