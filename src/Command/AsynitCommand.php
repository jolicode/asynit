<?php

namespace Asynit\Command;

use Amp\Loop;
use Asynit\Output\OutputFactory;
use Asynit\Parser\TestsFinder;
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
            ->addOption('tty', null, InputOption::VALUE_NONE, 'Force to use tty output')
            ->addOption('no-tty', null, InputOption::VALUE_NONE, 'Force to use no tty output')
            ->addOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Max number of parallels requests', 10)
            ->addOption('chrome-binary', null, InputOption::VALUE_REQUIRED, 'Path to the chrome binary', null)
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

        list($chainOutput, $countOutput) = (new OutputFactory())->buildOutput($input->getOption('tty'), $input->getOption('no-tty'));

        // Build services for parsing and running tests
        $testsFinder = new TestsFinder();
        $builder = new TestPoolBuilder(new AnnotationReader());
        $browser = new LazyChromeBrowser($input->getOption('chrome-binary'), null, new ConsoleLogger($output));
        $runner = new PoolRunner(new GuzzleMessageFactory(), new TestWorkflow($chainOutput), $browser, $input->getOption('concurrency'));

        // Build a list of tests from the directory
        $testMethods = $testsFinder->findTests($input->getArgument('target'));
        $pool = $builder->build($testMethods);

        Loop::run(function () use ($runner, $pool) {
            $runner->loop($pool);
        });

        // Return the number of failed tests
        return $countOutput->getFailed();
    }
}
