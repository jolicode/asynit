<?php

namespace Asynit\Runner;

use Amp\Future;
use Amp\Sync\LocalSemaphore;
use Amp\Sync\Semaphore;
use PHPUnit\Event\Code\TestMethodBuilder;
use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestCase\HookMethodInvoker;
use PHPUnit\Metadata\Api\HookMethods;

use function Amp\async;

/**
 * Runs the graph: every test whose dependencies are satisfied is started on its own fiber, up to the
 * configured concurrency, so tests waiting on I/O overlap.
 *
 * @internal
 */
final class ConcurrentRunner
{
    private Semaphore $semaphore;

    /** @var array<class-string, true> classes whose beforeClass hooks have run */
    private array $startedClasses = [];

    /** @var array<class-string, int> tests left to run per class, to know when afterClass is due */
    private array $remainingPerClass = [];

    /**
     * @param positive-int $concurrency
     */
    public function __construct(int $concurrency)
    {
        $this->semaphore = new LocalSemaphore($concurrency);
    }

    public function run(DependencyGraph $graph): void
    {
        foreach ($graph->nodes() as $node) {
            $class = $node->test()::class;
            $this->remainingPerClass[$class] = ($this->remainingPerClass[$class] ?? 0) + 1;
        }

        /** @var Future<mixed>[] $futures */
        $futures = [];

        while (!$graph->isEmpty()) {
            $node = $graph->next();

            if (null === $node) {
                if ([] === $futures) {
                    throw new \RuntimeException('Deadlock detected: tests are still pending but none of them can be run, and none is running.');
                }

                Future\awaitAny($futures);

                continue;
            }

            // Claimed before yielding to the event loop so the scheduler does not pick it up again while it
            // waits for a slot.
            $node->markAsScheduled();
            $key = spl_object_id($node);

            $futures[$key] = async(function () use ($node, $key, &$futures) {
                try {
                    $lock = $this->semaphore->acquire();

                    try {
                        $this->runNode($node);
                    } finally {
                        $lock->release();
                    }
                } finally {
                    unset($futures[$key]);
                }
            });
        }
    }

    private function runNode(TestNode $node): void
    {
        $test = $node->test();

        NodeStorage::set($node);

        try {
            $this->startClass($test);

            // Asynit resolves the dependency graph itself and feeds the produced values in as the test
            // method arguments, which is what PHPUnit does for its own #[Depends].
            $test->setDependencyInput($node->arguments());
            $test->run();

            $passed = $test->status()->isSuccess();
            $node->complete($passed, $test->result());

            if (!$passed) {
                $this->skipDependents($node);
            }
        } finally {
            NodeStorage::clear();
            $this->finishClass($test);
        }
    }

    /**
     * A test that never ran still has to be reported, otherwise it silently disappears from the run.
     */
    private function skipDependents(TestNode $node): void
    {
        foreach ($node->childrenToSkipOnFailure() as $child) {
            foreach ($child->skip() as $skipped) {
                $this->finishClass($skipped->test());

                if (!$skipped->isReported()) {
                    continue;
                }

                EventFacade::emitter()->testSkipped(
                    TestMethodBuilder::fromTestCase($skipped->test()),
                    'This test depends on a test that did not pass',
                );
            }
        }
    }

    private function startClass(TestCase $test): void
    {
        $class = $test::class;

        if (isset($this->startedClasses[$class])) {
            return;
        }

        $this->startedClasses[$class] = true;

        HookMethodInvoker::invokeBeforeClass($test, (new HookMethods())->hookMethods($class), EventFacade::emitter());
    }

    private function finishClass(TestCase $test): void
    {
        $class = $test::class;

        if (!isset($this->remainingPerClass[$class])) {
            return;
        }

        if (--$this->remainingPerClass[$class] > 0) {
            return;
        }

        unset($this->remainingPerClass[$class]);

        HookMethodInvoker::invokeAfterClass($test, (new HookMethods())->hookMethods($class), EventFacade::emitter());
    }
}
