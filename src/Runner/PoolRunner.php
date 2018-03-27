<?php

namespace Asynit\Runner;

use Amp\Loop;
use Amp\Promise;
use Amp\Sync\LocalSemaphore;
use Amp\Sync\Semaphore;
use Asynit\Pool;
use Asynit\Test;
use Asynit\TestCase;
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

    /** @var bool */
    private $allowSelfSignedCertificate;

    public function __construct(RequestFactory $requestFactory, TestWorkflow $workflow, $concurrency = 10, bool $allowSelfSignedCertificate = false)
    {
        $this->requestFactory = $requestFactory;
        $this->workflow = $workflow;
        $this->semaphore = new LocalSemaphore($concurrency);
        $this->allowSelfSignedCertificate = $allowSelfSignedCertificate;
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

            Loop::stop();
            ob_end_flush();
        });
    }

    protected function run(Test $test): Promise
    {
        return \Amp\call(function () use ($test) {
            try {
                $this->workflow->markTestAsRunning($test);

                $testCase = $this->buildTestCase($test);

                yield $testCase->initialize($this->allowSelfSignedCertificate);

                $result = yield \Amp\call(function () use ($testCase, $test) {
                    $method = $test->getMethod()->getName();
                    $args = $test->getArguments();

                    set_error_handler(__CLASS__.'::handleInternalError');

                    try {
                        return $testCase->$method(...$args);
                    } finally {
                        restore_error_handler();
                    }
                });

                foreach ($test->getChildren() as $childTest) {
                    $childTest->addArgument($result, $test);
                }

                $this->workflow->markTestAsSuccess($test);
            } catch (\Throwable $error) {
                $this->workflow->markTestAsFailed($test, $error);
            }
        });
    }

    private function buildTestCase(Test $test): TestCase
    {
        return $test->getMethod()->getDeclaringClass()->newInstance($this->requestFactory, $this->semaphore, $test);
    }

    public static function handleInternalError($type, $message, $file, $line)
    {
        $message = "$message in $file:$line";

        throw new \ErrorException($message, 0, $type, $file, $line);
    }
}
