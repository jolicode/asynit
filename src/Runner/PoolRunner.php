<?php

namespace Asynit\Runner;

use Amp\Loop;
use Amp\Parallel\Sync\Semaphore;
use Amp\Promise;
use Asynit\Assert\Assertion;
use Asynit\Output\OutputInterface;
use Asynit\Test;
use Asynit\TestCase;
use Asynit\Pool;
use Http\Message\RequestFactory;

class PoolRunner
{
    private $testObjects = [];

    /** @var OutputInterface */
    private $output;

    /** @var RequestFactory */
    private $requestFactory;

    /** @var Semaphore */
    private $semaphore;

    public function __construct(RequestFactory $requestFactory, OutputInterface $output, $concurrency = 10)
    {
        $this->requestFactory = $requestFactory;
        $this->output = $output;
        $this->semaphore = new SimpleSemaphore($concurrency);
    }

    public function loop(Pool $pool)
    {
        return \Amp\call(function () use($pool) {
            ob_start();
            $promises = [];

            while (!$pool->isEmpty()) {
                $test = $pool->getTestToRun();

                if ($test === null) {
                    yield \Amp\Promise\first($promises);

                    continue;
                }

                $promises[$test->getIdentifier()] = $this->run($test);
                $promises[$test->getIdentifier()]->onResolve(function () use (&$promises, $test) {
                    unset($promises[$test->getIdentifier()]);
                });
            }

            yield $promises;

            Loop::stop();
            ob_end_flush();
        });
    }

    protected function run(Test $test): Promise
    {
        return \Amp\call(function () use($test) {
            $debugOutput = ob_get_contents();
            ob_clean();

            $this->output->outputStep($test, $debugOutput);
            $test->setState(Test::STATE_RUNNING);

            $testCase = $this->getTestObject($test);
            $testCase->initialize();

            $method = $test->getMethod()->getName();
            $args = $test->getArguments();

            try {
                Assertion::$currentTest = $test;
                $result = yield \Amp\call(function () use($testCase, $method, $args) { return $testCase->$method(...$args); });

                foreach ($test->getChildren() as $childTest) {
                    $childTest->addArgument($result, $test);
                }

                $debugOutput = ob_get_contents();
                ob_clean();
                $this->output->outputSuccess($test, $debugOutput);
                $test->setState(Test::STATE_SUCCESS);
            } catch (\Throwable $error) {
                $debugOutput = ob_get_contents();
                ob_clean();

                $this->output->outputFailure($test, $debugOutput, $error);
                $test->setState(Test::STATE_FAILURE);
            }
        });
    }

    /**
     * Return a test case for a given test method.
     *
     * @param Test $test
     *
     * @return TestCase
     */
    private function getTestObject(Test $test): TestCase
    {
        $class = $test->getMethod()->getDeclaringClass()->getName();

        if (!array_key_exists($class, $this->testObjects)) {
            $this->testObjects[$class] = $test->getMethod()->getDeclaringClass()->newInstance($this->requestFactory, $this->semaphore);
        }

        return $this->testObjects[$class];
    }
}
