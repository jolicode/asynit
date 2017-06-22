<?php

namespace Asynit\Command;

use Asynit\Dns\ResolverFactory;
use Asynit\Output\Chain;
use Asynit\Output\Count;
use Asynit\Output\Detector;
use Asynit\Parser\Discovery;
use Asynit\Parser\TestPoolBuilder;
use Asynit\Runner\PoolRunner;
use Doctrine\Common\Annotations\AnnotationReader;
use Http\Adapter\React\Client as ReactAdapter;
use Http\Client\Common\Plugin\AddHostPlugin;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\PluginClient;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Message\UriFactory\GuzzleUriFactory;
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
            ->addOption('tty', null, InputOption::VALUE_NONE, 'Force to use tty output')
            ->addOption('no-tty', null, InputOption::VALUE_NONE, 'Force to use no tty output')
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
        $dnsResolver = (new ResolverFactory())->createCached($input->getOption('dns'), $loop);
        $connector = new DnsConnector(new TcpConnector($loop), $dnsResolver);

        // Build the HTTP Client
        $reactClient = new ReactAdapter($requestFactory, $loop, new ReactClient($connector, new SecureConnector($connector, $loop, [
            'allow_self_signed' => $input->getOption('allow-self-signed-certificate'),
        ])));

        $plugins = [
            new ContentLengthPlugin(),
        ];

        if ($input->hasOption('host') && null !== $input->getOption('host')) {
            $plugins[] = new AddHostPlugin($uriFactory->createUri($input->getOption('host')));
        }

        $httpClient = new PluginClient($reactClient, $plugins);

        // Build service for parsing and running tests
        $discovery = new Discovery();
        $builder = new TestPoolBuilder(new AnnotationReader());
        $countOutput = new Count();
        $chainOutput = new Chain();
        $chainOutput->addOutput((new Detector($loop))->detect($input->getOption('tty'), $input->getOption('no-tty')));
        $chainOutput->addOutput($countOutput);

        $runner = new PoolRunner($requestFactory, $httpClient, $loop, $chainOutput);

        // Build a list of tests from the directory
        $testMethods = $discovery->discover($input->getArgument('directory'));
        $pool = $builder->build($testMethods);

        // Run the list of tests
        $runner->run($pool);

        // Return the number of failed tests
        return $countOutput->getFailed();
    }
}
