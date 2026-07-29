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

use function Amp\async;

class PoolRunner
{
    private Semaphore $semaphore;

    /** @var object[] */
    private $testCases = [];

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
        // A chunk size of 1 makes PHP call the handler on every write, from the fiber that wrote it, which is
        // what allows output to be attributed to the test that produced it while tests run concurrently.
        ob_start($this->captureOutput(...), 1);

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

                // Claim the test before yielding to the event loop, so the scheduler does not pick it up again
                // while it waits for a slot on the semaphore.
                $test->markAsScheduled();

                $futures[$test->getIdentifier()] = async(function () use ($test, &$futures) {
                    try {
                        $lock = $this->semaphore->acquire();
                        TestStorage::set($test);

                        try {
                            // Marking the test as running here rather than at scheduling time keeps the time
                            // spent waiting for the semaphore out of the test duration.
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
            ob_end_flush();
        }
    }

    protected function run(Test $test): void
    {
        try {
            $testCase = $this->getTestCase($test);

            $method = $test->getMethod()->getName();
            $args = $test->getArguments();

            set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) {
                $message = "$errstr in $errfile:$errline";

                throw new \ErrorException($message, 0, $errno, $errfile, $errline);
            });

            try {
                $result = $testCase->$method(...$args);
            } finally {
                restore_error_handler();
            }

            foreach ($test->getChildren() as $childTest) {
                $childTest->addArgument($result, $test);
            }

            $this->workflow->markTestAsSuccess($test);
        } catch (\Throwable $error) {
            $this->workflow->markTestAsFailed($test, $error);
        }
    }

    /**
     * Output handler: everything a test writes is buffered on the test itself, anything else goes through.
     */
    private function captureOutput(string $buffer, int $phase): string
    {
        $test = TestStorage::get();

        if (null === $test) {
            return $buffer;
        }

        $test->appendOutput($buffer);

        return '';
    }

    private function getTestCase(Test $test): object
    {
        $reflectionClass = $test->testCaseClass;

        if (!isset($this->testCases[$reflectionClass->getName()])) {
            $testCase = $reflectionClass->newInstance();

            // Find all methods with attribute OnCreate
            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                $onCreate = $reflectionMethod->getAttributes(OnCreate::class);

                if (0 === count($onCreate)) {
                    continue;
                }

                $testCase->{$reflectionMethod->getName()}($this->defaultHttpConfiguration);
            }

            $this->testCases[$reflectionClass->getName()] = $testCase;
        }

        return $this->testCases[$reflectionClass->getName()];
    }
}
