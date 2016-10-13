<?php

namespace Asynit\Command;

use Asynit\Parser\Discovery;
use Http\Adapter\React\Client as ReactAdapter;
use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\PluginClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Message\UriFactory\GuzzleUriFactory;
use React\Dns\Resolver\Factory as DnsResolverFactory;
use React\EventLoop\Factory as EventLoopFactory;
use React\HttpClient\Client as ReactClient;
use React\SocketClient\DnsConnector;
use React\SocketClient\SecureConnector;
use React\SocketClient\TcpConnector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Run extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('run')
            ->addArgument('directory', InputArgument::REQUIRED, 'Path to the test directory')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Base host to use', null)
            ->addOption('allow-self-signed-certificate', null, InputOption::VALUE_NONE, 'Allow self signed ssl certificate')
            ->addOption('dns', null, InputOption::VALUE_OPTIONAL, 'DNS Ip to use', '8.8.8.8')
        ;
    }
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get PSR7 Factories
        $requestFactory = new GuzzleMessageFactory();
        $uriFactory = new GuzzleUriFactory();

        // Build the event loop
        $loop = EventLoopFactory::create();
        $dnsResolver = (new DnsResolverFactory())->createCached($input->getOption('dns'), $loop);
        $connector = new DnsConnector(new TcpConnector($loop), $dnsResolver);

        // Build the HTTP Client
        $reactClient = new ReactAdapter($requestFactory, $loop, new ReactClient($connector, new SecureConnector($connector, $loop, [
            'allow_self_signed' => $input->getOption('allow-self-signed-certificate'),
        ])));

        $plugins = [
            new ContentLengthPlugin()
        ];

        if ($input->hasOption('host') && null !== $input->getOption('host')) {
            $plugins[] = new AddHostPlugin($uriFactory->createUri($input->getOption('host')));
        }

        $httpClient = new PluginClient($reactClient, $plugins);

        // Build a list of tests from the directory
        $discovery = new Discovery();
        $testMethods = $discovery->discover($input->getArgument('directory'));

        var_dump($testMethods);

        // Run the list of tests

        /**
        $file = $input->getArgument('file');
        $loop = new StreamSelectLoop();
        $requestFactory = new GuzzleMessageFactory();
        $uriFactory = new GuzzleUriFactory();
        $dnsResolver = (new DnsResolverFactory())->createCached($input->getOption('dns'), $loop);
        $connector = new DnsConnector(new TcpConnector($loop), $dnsResolver);
        $reactClient = new ReactClient($connector, new SecureConnector($connector, $loop, [
            'allow_self_signed' => true,
        ]));
        $client = new Client($requestFactory, $loop, $reactClient);
        if ($input->hasOption('host') && null !== $input->getOption('host')) {
            $client = new PluginClient($client, [
                new AddHostPlugin($uriFactory->createUri($input->getOption('host'))),
            ]);
        }
        $client = new PluginClient($client, [
            new ContentLengthPlugin(),
        ]);
        $parser = new TestParser($requestFactory);
        $runner = new Runner($client, $loop, $requestFactory, 540);
        $tests = $parser->parse($file);
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
        return $runner->run($tests);
         **/
    }
}
