<?php

namespace Asynit\Runner;

use Amp\Loop;
use Amp\Sync\LocalSemaphore;
use Amp\Sync\Semaphore;
use Amp\Promise;
use Asynit\Test;
use Asynit\TestCase;
use Asynit\Pool;
use Asynit\TestWorkflow;
use Http\Message\RequestFactory;

class PoolRunner
{
    /** @var TestWorkflow */
    private $workflow;

    /** @var RequestFactory */
    private $requestFactory;

    /** @var Semaphore */
    private $semaphore;

    /** @var LazyChromeBrowser */
    private $lazyChromeBrowser;

    public function __construct(RequestFactory $requestFactory, TestWorkflow $workflow, LazyChromeBrowser $chromeBrowser, $concurrency = 10)
    {
        $this->requestFactory = $requestFactory;
        $this->workflow = $workflow;
        $this->semaphore = new LocalSemaphore($concurrency);
        $this->lazyChromeBrowser = $chromeBrowser;
    }

    public function loop(Pool $pool)
    {
        return \Amp\call(function () use ($pool) {
            ob_start();
            $promises = [];

            while (!$pool->isEmpty()) {
                $test = $pool->getTestToRun();

                if (null === $test) {
                    yield \Amp\Promise\first($promises);

                    continue;
                }

                $promises[$test->getIdentifier()] = $this->run($test);
                $promises[$test->getIdentifier()]->onResolve(function () use (&$promises, $test) {
                    unset($promises[$test->getIdentifier()]);
                });
            }

            yield $promises;

            $this->lazyChromeBrowser->shutdown();

            Loop::stop();
            ob_end_flush();
        });
    }

    protected function run(Test $test): Promise
    {
        return \Amp\call(function () use ($test) {
            try {
                $this->workflow->markTestAsRunning($test);

                $testCase = $this->getTestObject($test);
                yield $testCase->initialize();

                $method = $test->getMethod()->getName();
                $args = $test->getArguments();

                if ($test->hasChromeSession()) {
                    $session = yield $this->lazyChromeBrowser->getSession($test->getChromeSession());
                    $testCase->setSession($session);
                }

                $result = yield \Amp\call(function () use ($testCase, $method, $args) { return $testCase->$method(...$args); });

                foreach ($test->getChildren() as $childTest) {
                    $childTest->addArgument($result, $test);
                }

                $this->workflow->markTestAsSuccess($test);
            } catch (\Throwable $error) {
                $this->workflow->markTestAsFailed($test, $error);
            } finally {
                if ($test->hasChromeSession()) {
                    $this->lazyChromeBrowser->releaseSession($test->getChromeSession());
                }
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
        return $test->getMethod()->getDeclaringClass()->newInstance($this->requestFactory, $this->semaphore, $test);
    }
}
