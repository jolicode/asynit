<?php

namespace Asynit\Command;

use Amp\Loop;
use Asynit\Output\OutputFactory;
use Asynit\Parser\TestsFinder;
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

class AsynitCommand extends Command
{
    private $defaultBootstrapFilename;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->defaultBootstrapFilename = getcwd().'/vendor/autoload.php';

        $this
            ->setName('asynit')
            ->addArgument('target', InputArgument::REQUIRED, 'File or directory to test')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Base host to use', null)
            ->addOption('allow-self-signed-certificate', null, InputOption::VALUE_NONE, 'Allow self signed ssl certificate')
            ->addOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Max number of parallels requests', 10)
            ->addOption('bootstrap', null, InputOption::VALUE_REQUIRED, 'A PHP file to include before anything else', $this->defaultBootstrapFilename)
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bootstrapFilename = $input->getOption('bootstrap');
        if (file_exists($bootstrapFilename)) {
            require $bootstrapFilename;
        } elseif ($bootstrapFilename !== $this->defaultBootstrapFilename) {
            throw new \InvalidArgumentException("The bootstrap file '$bootstrapFilename' does not exist.");
        }

        $testsFinder = new TestsFinder();
        $testMethods = $testsFinder->findTests($input->getArgument('target'));

        list($chainOutput, $countOutput) = (new OutputFactory())->buildOutput(\count($testMethods));

        // Build services for parsing and running tests
        $builder = new TestPoolBuilder(new AnnotationReader());
        $runner = new PoolRunner(new GuzzleMessageFactory(), new TestWorkflow($chainOutput), $input->getOption('concurrency'));

        // Build a list of tests from the directory
        $pool = $builder->build($testMethods);

        Loop::run(function () use ($runner, $pool) {
            $runner->loop($pool);
        });

        // Return the number of failed tests
        return $countOutput->getFailed();
    }
}
