<?php

namespace Asynit;

use Asynit\Output\OutputInterface;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Event\Code\ThrowableBuilder;

/**
 * @internal
 */
final class TestWorkflow
{
    public function __construct(private OutputInterface $output)
    {
    }

    public function markTestAsRunning(Test $test): void
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->start();
        $this->output->outputStep($test, $test->output);
    }

    public function markTestAsSuccess(Test $test): void
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->success();
        $this->output->outputSuccess($test, $test->output);
    }

    public function markTestAsFailed(Test $test, ?Throwable $error, bool $isAssertion): void
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->failure($error, $isAssertion);
        $this->output->outputFailure($test, $test->output, $error);

        foreach ($test->getChildren(true) as $child) {
            $this->markTestAsSkipped($child);
        }
    }

    /**
     * The test case could not even be built, so PHPUnit never saw it and emitted no event.
     */
    public function markTestAsFailedToStart(Test $test, \Throwable $error): void
    {
        $this->markTestAsFailed($test, ThrowableBuilder::from($error), false);
    }

    public function markTestAsSkipped(Test $test): void
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->skipped();

        foreach ($test->getChildren() as $child) {
            $this->markTestAsSkipped($child);
        }

        $this->output->outputSkipped($test, $test->output);
    }
}
