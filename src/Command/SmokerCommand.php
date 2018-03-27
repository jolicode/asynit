<?php

declare(strict_types=1);

namespace Asynit\Command;

use Amp\Loop;
use Asynit\Output\OutputFactory;
use Asynit\Parser\SmokeParser;
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
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Base host to use', null)
            ->addOption('allow-self-signed-certificate', null, InputOption::VALUE_NONE, 'Allow self signed ssl certificate')
            ->addOption('concurrency', null, InputOption::VALUE_OPTIONAL, 'Max number of parallels requests', 10)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Build the client
        $parser = new SmokeParser();
        $testMethods = $parser->parse($input->getArgument('file'));

        list($chainOutput, $countOutput) = (new OutputFactory())->buildOutput(\count($testMethods));

        $builder = new TestPoolBuilder(new AnnotationReader());
        $runner = new PoolRunner(new GuzzleMessageFactory(), new TestWorkflow($chainOutput), $input->getOption('concurrency'));

        $pool = $builder->build($testMethods);

        // Run the list of tests
        Loop::run(function () use ($runner, $pool) {
            $runner->loop($pool);
        });

        // Return the number of failed tests
        return $countOutput->getFailed();
    }
}
