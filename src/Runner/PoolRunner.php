<?php

namespace Asynit\Runner;

use function Amp\async;
use Amp\Future;
use Amp\Sync\LocalSemaphore;
use Amp\Sync\Semaphore;
use Asynit\Attribute\OnCreate;
use Asynit\Pool;
use Asynit\Test;
use Asynit\TestWorkflow;

class PoolRunner
{
    private Semaphore $semaphore;

    private $testCases = [];

    public function __construct(private TestWorkflow $workflow, int $concurrency = 10)
    {
        $this->semaphore = new LocalSemaphore($concurrency);
    }

    public function loop(Pool $pool)
    {
        ob_start();
        /** @var Future[] $futures */
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
                $this->run($test);
                $lock->release();

                unset($futures[$test->getIdentifier()]);
            });
        }
        ob_end_flush();
    }

    protected function run(Test $test)
    {
        try {
            $testCase = $this->getTestCase($test);

            $method = $test->getMethod()->getName();
            $args = $test->getArguments();

            set_error_handler(__CLASS__.'::handleInternalError');

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

    private function getTestCase(Test $test)
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

                $testCase->{$reflectionMethod->getName()}($test);
            }

            $this->testCases[$reflectionClass->getName()] = $testCase;
        }

        return $this->testCases[$reflectionClass->getName()];
    }

    public static function handleInternalError($type, $message, $file, $line)
    {
        $message = "$message in $file:$line";

        throw new \ErrorException($message, 0, $type, $file, $line);
    }
}
