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

        $debugOutput = ob_get_contents();
        ob_clean();

        $test->start();
        $this->output->outputStep($test, false === $debugOutput ? '' : $debugOutput);
    }

    public function markTestAsSuccess(Test $test): void
    {
        if ($test->isCompleted()) {
            return;
        }

        $debugOutput = ob_get_contents();
        ob_clean();

        $test->success(false === $debugOutput ? '' : $debugOutput);
        $this->output->outputSuccess($test, false === $debugOutput ? '' : $debugOutput);
    }

    public function markTestAsFailed(Test $test, \Throwable $error): void
    {
        if ($test->isCompleted()) {
            return;
        }

        $debugOutput = ob_get_contents();
        ob_clean();

        $test->failure(false === $debugOutput ? '' : $debugOutput, $error);
        $this->output->outputFailure($test, false === $debugOutput ? '' : $debugOutput, $error);

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

        $debugOutput = ob_get_contents();
        ob_clean();
        $this->output->outputSkipped($test, false === $debugOutput ? '' : $debugOutput);
    }
}
