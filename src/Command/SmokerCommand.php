<?php

declare(strict_types=1);

namespace Asynit\Command;

use Amp\Loop;
use Asynit\Output\OutputFactory;
use Asynit\Parser\SmokeParser;
use Asynit\Parser\TestPoolBuilder;
use Asynit\Runner\LazyChromeBrowser;
use Asynit\Runner\PoolRunner;
use Asynit\TestWorkflow;
use Doctrine\Common\Annotations\AnnotationReader;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class SmokerCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('smoke')
            ->addArgument('file', InputArgument::REQUIRED, 'Configuration file for smoker')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Base host to use', '')
            ->addOption('allow-insecure-certificate', null, InputOption::VALUE_NONE, 'Allow insecure certificate (like self signed)')
            ->addOption('tty', null, InputOption::VALUE_NONE, 'Force to use tty output')
            ->addOption('no-tty', null, InputOption::VALUE_NONE, 'Force to use no tty output')
            ->addOption('chrome-binary', null, InputOption::VALUE_REQUIRED, 'Path to the chrome binary', null)
            ->addOption('concurrency', null, InputOption::VALUE_OPTIONAL, 'Max number of parallels requests', 10)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Build the client
        list($chainOutput, $countOutput) = (new OutputFactory())->buildOutput($input->getOption('tty'), $input->getOption('no-tty'));

        $parser = new SmokeParser($input->getOption('host'));
        $builder = new TestPoolBuilder(new AnnotationReader());
        $browser = new LazyChromeBrowser($input->getOption('chrome-binary'), null, new ConsoleLogger($output));
        $runner = new PoolRunner(new GuzzleMessageFactory(), new TestWorkflow($chainOutput), $browser, $input->getOption('concurrency'));

        $testMethods = $parser->parse($input->getArgument('file'));
        $pool = $builder->build($testMethods);

        // Run the list of tests
        Loop::run(function () use ($runner, $pool) {
            $runner->loop($pool);
        });

        // Return the number of failed tests
        return $countOutput->getFailed();
    }
}
