<?php

namespace Asynit;

use Asynit\Output\OutputInterface;

class TestWorkflow
{
    public function __construct(private OutputInterface $output)
    {
    }

    public function markTestAsRunning(Test $test): void
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->setState(Test::STATE_RUNNING);

        $debugOutput = ob_get_contents();
        ob_clean();

        $this->output->outputStep($test, $debugOutput);
    }

    public function markTestAsSuccess(Test $test): void
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->setState(Test::STATE_SUCCESS);

        $debugOutput = ob_get_contents();
        ob_clean();
        $this->output->outputSuccess($test, $debugOutput);
    }

    public function markTestAsFailed(Test $test, \Throwable $error): void
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->setState(Test::STATE_FAILURE);

        $debugOutput = ob_get_contents();
        ob_clean();

        if (is_string($debugOutput)) {
            $this->output->outputFailure($test, $debugOutput, $error);
        }

        foreach ($test->getChildren() as $child) {
            $this->markTestAsSkipped($child);
        }
    }

    public function markTestAsSkipped(Test $test): void
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->setState(Test::STATE_SKIPPED);

        foreach ($test->getChildren() as $child) {
            $this->markTestAsSkipped($child);
        }

        $debugOutput = ob_get_contents();
        ob_clean();
        $this->output->outputSkipped($test, $debugOutput);
    }
}
