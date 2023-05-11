<?php

namespace Asynit\Command;

use Asynit\Output\OutputFactory;
use Asynit\Parser\TestPoolBuilder;
use Asynit\Parser\TestsFinder;
use Asynit\Runner\PoolRunner;
use Asynit\TestWorkflow;
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
            ->addOption('order', null, InputOption::VALUE_NONE, 'Output tests execution order')
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

        list($chainOutput, $countOutput) = (new OutputFactory($input->getOption('order')))->buildOutput(\count($testMethods));

        $builder = new TestPoolBuilder();
        $runner = new PoolRunner(new TestWorkflow($chainOutput), $input->getOption('concurrency'));

        // Build a list of tests from the directory
        $pool = $builder->build($testMethods);
        $runner->loop($pool);

        // Return the number of failed tests
        return $countOutput->getFailed();
    }
}
