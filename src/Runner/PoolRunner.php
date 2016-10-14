<?php

namespace Asynit\Runner;

use Asynit\Output\OutputInterface;
use Asynit\Test;
use Asynit\TestCase;
use Asynit\Pool;
use Http\Client\Exception;
use Http\Client\HttpAsyncClient;
use Http\Message\RequestFactory;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;

class PoolRunner
{
    private $testObjects = [];

    /** @var OutputInterface */
    private $output;

    /** @var HttpAsyncClient */
    private $httpClient;

    /** @var FutureHttpPool */
    private $futureHttpPool;

    /** @var int */
    private $concurrency;

    /** @var LoopInterface */
    private $loop;

    /** @var RequestFactory */
    private $requestFactory;

    public function __construct(RequestFactory $requestFactory, HttpAsyncClient $httpAsyncClient, LoopInterface $loop, OutputInterface $output, $concurrency = 10)
    {
        $this->requestFactory = $requestFactory;
        $this->httpClient = $httpAsyncClient;
        $this->loop = $loop;
        $this->output = $output;
        $this->concurrency = $concurrency;
        $this->futureHttpPool = new FutureHttpPool();
    }

    /**
     * Run a test pool.
     *
     * @param Pool $pool
     *
     * @return int The number of failed tests
     */
    public function run(Pool $pool)
    {
        $failedTest = 0;
        ob_start();

        while (!$pool->isEmpty()) {
            while ($pool->countRunningHttp() >= $this->concurrency) {
                $this->loop->tick();
            }

            $test = $pool->passRunningTest();

            if (null === $test) {
                // No more tests are available let the loop end
                $this->loop->tick();

                continue;
            }

            // Prepare test callback
            $testCase = $this->getTestObject($test);
            $method = $test->getMethod()->getName();
            $httpClient = $testCase->setUp($this->httpClient);
            $args = $test->getArguments();

            if ($test->getMethod()->returnsReference()) {
                $executeCallback = function &() use ($testCase, $method, $args) {
                    return $testCase->$method(...$args);
                };
            } else {
                $executeCallback = function () use ($testCase, $method, $args) {
                    return $testCase->$method(...$args);
                };
            }

            if (!$this->executeTestStep($executeCallback, $test, $pool, true)) {
                ++$failedTest;
            }

            do {
                $futureHttp = $pool->passRunningHttp();

                if (null === $futureHttp) {
                    $this->loop->tick();

                    continue;
                }

                $request = $futureHttp->getRequest();

                $httpClient->sendAsyncRequest($request)->then(
                    function (ResponseInterface $response) use ($test, $pool, $futureHttp, &$failedTest) {
                        $test->getFutureHttpPool()->removeElement($futureHttp);
                        $pool->passFinishHttp($futureHttp);

                        $this->executeTestStep(function () use ($response, $futureHttp) {
                            $assertCallback = $futureHttp->getResolveCallback();
                            $assertCallback($response);
                        }, $test, $pool);
                    },
                    function (Exception $exception) use ($test, $pool, $futureHttp, &$failedTest) {
                        $test->getFutureHttpPool()->removeElement($futureHttp);
                        $pool->passFinishHttp($futureHttp);

                        $this->executeTestStep(function () use ($exception) {
                            throw $exception;
                        }, $test, $pool);
                    }
                );
            } while ($pool->countPendingHttp() > 0 && $pool->countRunningHttp() < $this->concurrency);
        }

        // Output remaining
        ob_end_flush();

        return $failedTest;
    }

    /**
     * Execute a test step.
     *
     * @param callable $callback
     * @param Test     $test
     * @param Pool     $pool
     *
     * @return bool
     */
    protected function executeTestStep($callback, Test $test, Pool $pool, $isTestMethod = false)
    {
        try {
            if ($test->getMethod()->returnsReference() && $isTestMethod) {
                $result = &$callback();
            } else {
                $result = $callback();
            }

            $futureHttpCollection = $this->futureHttpPool->flush();
            $test->getFutureHttpPool()->merge($futureHttpCollection);
            $pool->queueFutureHttp($futureHttpCollection);
        } catch (\Throwable $exception) {
            $debugOutput = ob_get_contents();
            ob_clean();

            if ($test->getFutureHttpPool()->isEmpty()) {
                $pool->passFinishTest($test);
            }

            $this->output->outputFailure($test, $debugOutput, $exception);

            return false;
        } catch (\Exception $exception) {
            $debugOutput = ob_get_contents();
            ob_clean();

            if ($test->getFutureHttpPool()->isEmpty()) {
                $pool->passFinishTest($test);
            }

            $this->output->outputFailure($test, $debugOutput, $exception);

            return false;
        }

        $debugOutput = ob_get_contents();
        ob_clean();

        if ($isTestMethod) {
            foreach ($test->getChildren() as $childTest) {
                $childTest->addArgument($result, $test);
            }
        }

        if ($test->getFutureHttpPool()->isEmpty()) {
            $pool->passFinishTest($test);
            $this->output->outputSuccess($test, $debugOutput);

            foreach ($test->getChildren() as $childTest) {
                $complete = true;

                foreach ($childTest->getParents() as $parentTest) {
                    if ($pool->hasTest($parentTest)) {
                        $complete = false;
                    }
                }

                if ($complete) {
                    $pool->queueTest($childTest);
                }
            }

            return true;
        }

        $this->output->outputStep($test, $debugOutput);

        return true;
    }

    /**
     * Return a test case for a given test method.
     *
     * @param Test $test
     *
     * @return TestCase
     */
    protected function getTestObject(Test $test)
    {
        $class = $test->getMethod()->getDeclaringClass()->getName();

        if (!array_key_exists($class, $this->testObjects)) {
            $this->testObjects[$class] = $test->getMethod()->getDeclaringClass()->newInstance($this->requestFactory, $this->futureHttpPool);
        }

        return $this->testObjects[$class];
    }
}
