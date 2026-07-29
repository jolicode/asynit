<?php

namespace Asynit;

use Asynit\Output\OutputInterface;

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

    public function markTestAsFailed(Test $test, \Throwable $error): void
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->failure($error);
        $this->output->outputFailure($test, $test->output, $error);

        foreach ($test->getChildren(true) as $child) {
            $this->markTestAsSkipped($child);
        }
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
