<?php

namespace Asynit\Runner;

use Asynit\Assert\Assertion;
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

            if (!$this->passTest($pool)) {
                ++$failedTest;
            }

            $this->passHttp($pool);

            $this->loop->tick();
        }

        // Output remaining
        ob_end_flush();

        return $failedTest;
    }

    /**
     * Add a new request to the running pool if available
     *
     * @param Pool $pool
     */
    protected function passHttp(Pool $pool)
    {
        $futureHttp = $pool->passRunningHttp();

        if (null !== $futureHttp) {
            $request = $futureHttp->getRequest();
            $httpClient = $futureHttp->getTest()->getHttpClient();
            $httpClient->sendAsyncRequest($request)->then(
                function (ResponseInterface $response) use ($pool, $futureHttp) {
                    $test = $futureHttp->getTest();

                    $test->getFutureHttpPool()->removeElement($futureHttp);
                    $pool->passFinishHttp($futureHttp);

                    $this->executeTestStep(function () use ($response, $futureHttp) {
                        $assertCallback = $futureHttp->getResolveCallback();
                        $assertCallback($response);
                    }, $test, $pool);
                },
                function (Exception $exception) use ($pool, $futureHttp) {
                    $test = $futureHttp->getTest();

                    $test->getFutureHttpPool()->removeElement($futureHttp);
                    $pool->passFinishHttp($futureHttp);

                    $this->executeTestStep(function () use ($exception) {
                        throw $exception;
                    }, $test, $pool);
                }
            );
        }
    }

    /**
     * Add a new test to the running pool
     *
     * @param Pool $pool
     *
     * @return bool
     */
    protected function passTest(Pool $pool)
    {
        $test = $pool->passRunningTest();

        if (null !== $test) {
            // Prepare test callback
            $testCase = $this->getTestObject($test);
            $method = $test->getMethod()->getName();
            $test->setHttpClient($testCase->setUp($this->httpClient));
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
                return false;
            }
        }

        return true;
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
            Assertion::$currentTest = $test;

            if ($isTestMethod && $test->getMethod()->returnsReference()) {
                $result = &$callback();
            } else {
                $result = $callback();
            }

            $futureHttpCollection = $this->futureHttpPool->flush();
            $test->mergeFutureHttp($futureHttpCollection, $test);
            $pool->queueFutureHttp($futureHttpCollection);
        } catch (\Throwable $exception) {
            $debugOutput = ob_get_contents();
            ob_clean();

            $this->futureHttpPool->flush();
            $pool->passFinishTest($test);
            $this->output->outputFailure($test, $debugOutput, $exception);

            return false;
        } catch (\Exception $exception) {
            $debugOutput = ob_get_contents();
            ob_clean();

            $this->futureHttpPool->flush();
            $pool->passFinishTest($test);
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

        if ($pool->hasTest($test) && $test->getFutureHttpPool()->isEmpty()) {
            $pool->passFinishTest($test);
            $this->output->outputSuccess($test, $debugOutput);

            foreach ($test->getChildren() as $childTest) {
                $complete = true;

                foreach ($childTest->getParents() as $parentTest) {
                    if (!$pool->hasCompletedTest($parentTest)) {
                        $complete = false;
                        break;
                    }
                }

                if ($complete) {
                    $pool->queueTest($childTest);
                }
            }

            return true;
        }

        if ($pool->hasTest($test)) {
            $this->output->outputStep($test, $debugOutput);
        }

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
