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
        int $concurrency = 10
    ) {
        $this->semaphore = new LocalSemaphore($concurrency);
    }

    public function loop(Pool $pool): void
    {
        ob_start();
        /** @var Future<mixed>[] $futures */
        $futures = [];

        while (!$pool->isEmpty()) {
            $test = $pool->getTestToRun();

            if (null === $test) {
                Future\awaitAny($futures);

                continue;
            }

            $this->workflow->markTestAsRunning($test);

            $futures[$test->getIdentifier()] = async(function () use ($test, &$futures) {
                $lock = $this->semaphore->acquire();
                TestStorage::set($test);

                $this->run($test);
                $lock->release();

                unset($futures[$test->getIdentifier()]);
            });
        }
        ob_end_flush();
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

    private function getTestCase(Test $test): object
    {
        $reflectionClass = $test->getMethod()->getDeclaringClass();

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
