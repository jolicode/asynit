<?php

namespace Asynit\Command;

use Asynit\Attribute\HttpClientConfiguration;
use Asynit\Output\OutputFactory;
use Asynit\Parser\TestPoolBuilder;
use Asynit\Parser\TestsFinder;
use Asynit\Report\JUnitReport;
use Asynit\Runner\PoolRunner;
use Asynit\TestWorkflow;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
final class AsynitCommand extends Command
{
    private string $defaultBootstrapFilename = '';

    protected function configure(): void
    {
        $this->defaultBootstrapFilename = getcwd().'/vendor/autoload.php';

        $this
            ->setName('asynit')
            ->addArgument('target', InputArgument::REQUIRED, 'File or directory to test')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Base URI prepended to requests made with a relative URI, e.g. https://api.example.com', null)
            ->addOption('allow-self-signed-certificate', null, InputOption::VALUE_NONE, 'Allow self signed ssl certificate')
            ->addOption('concurrency', null, InputOption::VALUE_REQUIRED, 'Max number of parallels requests', 10)
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'Default timeout for http request', 10)
            ->addOption('retry', null, InputOption::VALUE_REQUIRED, 'Default retry number for http request', 0)
            ->addOption('bootstrap', null, InputOption::VALUE_REQUIRED, 'A PHP file to include before anything else', $this->defaultBootstrapFilename)
            ->addOption('order', null, InputOption::VALUE_NONE, 'Output tests execution order')
            ->addOption('report', null, InputOption::VALUE_REQUIRED, 'JUnit report directory')
            ->addOption('filter', null, InputOption::VALUE_REQUIRED, 'Filter test with a regex')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $bootstrapFilename */
        $bootstrapFilename = $input->getOption('bootstrap');
        if (file_exists($bootstrapFilename)) {
            require $bootstrapFilename;
        } elseif ($bootstrapFilename !== $this->defaultBootstrapFilename) {
            throw new \InvalidArgumentException("The bootstrap file '$bootstrapFilename' does not exist.");
        }

        /** @var string $target */
        $target = $input->getArgument('target');
        /** @var ?string $filter */
        $filter = $input->getOption('filter');

        $testsFinder = new TestsFinder();
        $testsSuites = $testsFinder->findTests($target, $filter);
        $testsCount = array_reduce($testsSuites, fn (int $carry, $suite) => $carry + \count($suite->tests), 0);

        // Build the pool before anything is displayed, so that a pool that cannot be built (an unresolvable or
        // circular dependency) reports the error on its own instead of below a test suite summary.
        $builder = new TestPoolBuilder();
        $pool = $builder->build($testsSuites);

        $useOrder = (bool) $input->getOption('order');

        list($chainOutput, $countOutput) = (new OutputFactory($useOrder))->buildOutput($testsCount);

        /** @phpstan-ignore-next-line */
        $timeout = (float) $input->getOption('timeout');
        /** @phpstan-ignore-next-line */
        $retry = (int) $input->getOption('retry');
        /** @phpstan-ignore-next-line */
        $concurrency = (int) $input->getOption('concurrency');

        if ($concurrency < 1) {
            throw new \InvalidArgumentException('Concurrency must be greater than 0');
        }

        /** @var string|null $host */
        $host = $input->getOption('host');

        if (null !== $host && !preg_match('#^https?://[^/]+#i', $host)) {
            throw new \InvalidArgumentException(sprintf('The host "%s" must be an absolute URI, e.g. https://api.example.com.', $host));
        }

        $defaultHttpConfiguration = new HttpClientConfiguration(
            timeout: $timeout,
            retry: $retry,
            allowSelfSignedCertificate: (bool) $input->getOption('allow-self-signed-certificate'),
            baseUri: $host,
        );

        $runner = new PoolRunner($defaultHttpConfiguration, new TestWorkflow($chainOutput), $concurrency);

        $start = microtime(true);
        $runner->loop($pool);
        $end = microtime(true);

        /** @var string|null $reportDir */
        $reportDir = $input->getOption('report');

        if (null !== $reportDir) {
            $report = new JUnitReport($reportDir);
            $time = $end - $start;
            $report->generate($time, $testsSuites);
        }

        // Return the number of failed tests
        return $countOutput->getFailed();
    }
}
