<?php

namespace Asynit\Command;

use Amp\Loop;
use Asynit\Factory;
use Asynit\Parser\Discovery;
use Asynit\Parser\TestPoolBuilder;
use Asynit\Runner\PoolRunner;
use Asynit\TestWorkflow;
use Doctrine\Common\Annotations\AnnotationReader;
use Http\Message\MessageFactory\GuzzleMessageFactory;
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
            ->addOption('concurrency', null, InputOption::VALUE_OPTIONAL, 'Max number of parallels requests', 10)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Build the client
        list($chainOutput, $countOutput) = Factory::createOutput($input->getOption('tty'), $input->getOption('no-tty'));

        // Build service for parsing and running tests
        $discovery = new Discovery();
        $builder = new TestPoolBuilder(new AnnotationReader());
        $runner = new PoolRunner(new GuzzleMessageFactory(), new TestWorkflow($chainOutput), $input->getOption('concurrency'));

        // Build a list of tests from the directory
        $testMethods = $discovery->discover($input->getArgument('directory'));
        $pool = $builder->build($testMethods);

        Loop::run(function () use ($runner, $pool) {
            $runner->loop($pool);
        });

        // Return the number of failed tests
        return $countOutput->getFailed();
    }
}
