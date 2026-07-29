<?php

namespace Asynit\Runner;

use Amp\Future;
use Amp\Sync\LocalSemaphore;
use Amp\Sync\Semaphore;
use Asynit\Attribute\HttpClientConfiguration;
use Asynit\Attribute\OnCreate;
use Asynit\Pool;
use Asynit\Test;
use Asynit\TestWorkflow;
use PHPUnit\Framework\TestCase;

use function Amp\async;

class PoolRunner
{
    private Semaphore $semaphore;

    /**
     * @param positive-int $concurrency
     */
    public function __construct(
        private HttpClientConfiguration $defaultHttpConfiguration,
        private TestWorkflow $workflow,
        int $concurrency = 10,
    ) {
        $this->semaphore = new LocalSemaphore($concurrency);
    }

    public function loop(Pool $pool): void
    {
        // One error handler for the whole run rather than one per test: set_error_handler() is a process wide
        // LIFO stack, and concurrent tests do not unwind it in order.
        set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) {
            throw new \ErrorException("$errstr in $errfile:$errline", 0, $errno, $errfile, $errline);
        });

        try {
            /** @var Future<mixed>[] $futures */
            $futures = [];

            while (!$pool->isEmpty()) {
                $test = $pool->getNextTestToRun();

                if (null === $test) {
                    if ([] === $futures) {
                        throw new \RuntimeException('Deadlock detected: some tests are still pending but none of them can be run, and no test is running.');
                    }

                    Future\awaitAny($futures);

                    continue;
                }

                $test->markAsScheduled();

                $futures[$test->getIdentifier()] = async(function () use ($test, &$futures) {
                    try {
                        $lock = $this->semaphore->acquire();
                        TestStorage::set($test);

                        try {
                            $this->workflow->markTestAsRunning($test);
                            $this->run($test);
                        } finally {
                            TestStorage::clear();
                            $lock->release();
                        }
                    } finally {
                        unset($futures[$test->getIdentifier()]);
                    }
                });
            }
        } finally {
            restore_error_handler();
        }
    }

    protected function run(Test $test): void
    {
        try {
            $testCase = $this->createTestCase($test);
        } catch (\Throwable $error) {
            // Nothing ran, so PHPUnit emitted no event we could collect a throwable from.
            $this->workflow->markTestAsFailedToStart($test, $error);

            return;
        }

        // Asynit resolves the dependency graph itself, including dependencies on methods that are not tests
        // and on other classes, then feeds the produced values in as the test method arguments.
        $testCase->setDependencyInput($test->getArguments());

        $testCase->run();

        $test->output = $testCase->output();
        $test->assertionCount = $testCase->numberOfAssertionsPerformed();

        $status = $testCase->status();

        if ($status->isSuccess()) {
            foreach ($test->getChildren() as $childTest) {
                $childTest->addArgument($testCase->result(), $test);
            }

            $this->workflow->markTestAsSuccess($test);

            return;
        }

        if ($status->isSkipped() || $status->isIncomplete()) {
            $this->workflow->markTestAsSkipped($test);

            return;
        }

        // The throwable was pushed onto the test by ResultCollector while the test was still on its own fiber.
        $this->workflow->markTestAsFailed($test, $test->failure, $test->failureIsAssertion);
    }

    private function createTestCase(Test $test): TestCase
    {
        $className = $test->testCaseClass->getName();

        // Unlike asynit's own engine, PHPUnit binds one instance to one test method, so there is no instance
        // to share across the tests of a class.
        $testCase = new $className($test->getMethod()->getName());

        foreach ($test->testCaseClass->getMethods() as $reflectionMethod) {
            if (0 === count($reflectionMethod->getAttributes(OnCreate::class))) {
                continue;
            }

            $testCase->{$reflectionMethod->getName()}($this->defaultHttpConfiguration);
        }

        return $testCase;
    }
}
