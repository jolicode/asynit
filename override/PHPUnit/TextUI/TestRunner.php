<?php

declare(strict_types=1);

namespace PHPUnit\TextUI;

use Asynit\Runner\ConcurrentRunner;
use Asynit\Runner\DependencyGraph;
use Asynit\Runner\StackTraceFilter;
use PHPUnit\Event;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Runner\ResultCache\ResultCache;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * The one seam asynit needs from PHPUnit: everything around this class is PHPUnit's - the CLI, the
 * configuration, the extensions, the printers, the loggers, the exit codes - and only the execution of the
 * collected tests is replaced.
 *
 * The original walks the suite tree and calls $test->run() one test at a time. This one flattens the tree,
 * builds asynit's #[Depend] graph over the tests and runs them concurrently on fibers, so tests waiting on
 * I/O overlap.
 *
 * @internal
 */
final class TestRunner
{
    /**
     * @throws RuntimeException
     */
    public function run(Configuration $configuration, ResultCache $resultCache, TestSuite $suite): void
    {
        try {
            Event\Facade::emitter()->testRunnerStarted();

            StackTraceFilter::register();

            // Asynit orders by its dependency graph, so PHPUnit's sorting is deliberately not applied.
            (new TestSuiteFilterProcessor())->process($configuration, $suite);

            // The graph is built before the run is announced, so producers pulled in by #[Depend] are part of
            // the suite the reporters are told about.
            $graph = $this->execute($suite);

            Event\Facade::emitter()->testRunnerExecutionStarted(
                Event\TestSuite\TestSuiteBuilder::from($suite),
            );

            $this->runGraph($suite, $graph);

            Event\Facade::emitter()->testRunnerExecutionFinished();
            Event\Facade::emitter()->testRunnerFinished();
        } catch (\Throwable $t) {
            throw new RuntimeException($t->getMessage(), (int) $t->getCode(), $t);
        }
    }

    private function execute(TestSuite $suite): DependencyGraph
    {
        $tests = [];
        self::collect($suite, $tests);

        $graph = DependencyGraph::build($tests);

        // The graph may have added producers - methods something depends on that PHPUnit did not collect as
        // tests. They are added to the suite so the progress counter and the totals account for them.
        foreach ($graph->nodes() as $node) {
            if (!$node->isReported()) {
                $suite->addTest($node->test());
            }
        }

        return $graph;
    }

    private function runGraph(TestSuite $suite, DependencyGraph $graph): void
    {
        if ([] === $graph->nodes()) {
            return;
        }

        $suiteForEvents = Event\TestSuite\TestSuiteBuilder::from($suite);
        Event\Facade::emitter()->testSuiteStarted($suiteForEvents);

        try {
            new ConcurrentRunner(self::concurrency())->run($graph);
        } finally {
            Event\Facade::emitter()->testSuiteFinished($suiteForEvents);
        }
    }

    /**
     * @param TestCase[] $tests
     */
    private static function collect(TestSuite $suite, array &$tests): void
    {
        foreach ($suite as $test) {
            if ($test instanceof TestSuite) {
                self::collect($test, $tests);

                continue;
            }

            if ($test instanceof TestCase) {
                $tests[] = $test;
            }
        }
    }

    /**
     * PHPUnit's CLI is not asynit's to extend, so the concurrency is read from the environment.
     *
     * @return positive-int
     */
    private static function concurrency(): int
    {
        $value = $_SERVER['ASYNIT_CONCURRENCY'] ?? getenv('ASYNIT_CONCURRENCY');
        $concurrency = is_scalar($value) ? (int) $value : 0;

        return $concurrency > 0 ? $concurrency : 10;
    }
}
