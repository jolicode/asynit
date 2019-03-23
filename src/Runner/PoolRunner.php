<?php

namespace Asynit\Runner;

use Asynit\Test;
use Asynit\TestCase;
use Asynit\Pool;
use Asynit\TestWorkflow;
use function Concurrent\all;
use function Concurrent\race;
use Concurrent\Task;

class PoolRunner
{
    private $workflow;

    private $outputBuffering;

    public function __construct(TestWorkflow $workflow, bool $outputBuffering = false)
    {
        $this->workflow = $workflow;
        $this->outputBuffering = $outputBuffering;
    }

    public function loop(Pool $pool)
    {
        if ($this->outputBuffering) {
            ob_start();
        }

        $tasks = [];

        while (!$pool->isEmpty()) {
            $test = $pool->getTestToRun();

            if (null === $test) {
                Task::await(race($tasks));

                continue;
            }

            $this->workflow->markTestAsRunning($test);

            $task = Task::async(function () use ($test, &$tasks) {
                $this->run($test);
                unset($tasks[$test->getIdentifier()]);
            });

            $tasks[$test->getIdentifier()] = $task;
        }

        if (count($tasks) > 0) {
            Task::await(all($tasks));
        }

        if ($this->outputBuffering) {
            ob_end_flush();
        }
    }

    protected function run(Test $test)
    {
        try {
            $testCase = $this->buildTestCase($test);

            $method = $test->getMethod()->getName();
            $args = $test->getArguments();

            set_error_handler(__CLASS__.'::handleInternalError');

            try {
                $testCase->setUp();
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

    private function buildTestCase(Test $test): TestCase
    {
        return $test->getMethod()->getDeclaringClass()->newInstance($test);
    }

    public static function handleInternalError($type, $message, $file, $line)
    {
        $message = "$message in $file:$line";

        throw new \ErrorException($message, 0, $type, $file, $line);
    }
}
