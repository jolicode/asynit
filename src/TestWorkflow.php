<?php

namespace Asynit;

use Asynit\Output\OutputInterface;

class TestWorkflow
{
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function markTestAsRunning(Test $test)
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->setState(Test::STATE_RUNNING);

        $debugOutput = ob_get_contents();
        ob_clean();

        $this->output->outputStep($test, $debugOutput);
    }


    public function markTestAsSuccess(Test $test)
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->setState(Test::STATE_SUCCESS);

        $debugOutput = ob_get_contents();
        ob_clean();
        $this->output->outputSuccess($test, $debugOutput);
    }

    public function markTestAsFailed(Test $test, \Throwable $error)
    {
        if ($test->isCompleted()) {
            return;
        }

        $test->setState(Test::STATE_FAILURE);

        $debugOutput = ob_get_contents();
        ob_clean();
        $this->output->outputFailure($test, $debugOutput, $error);

        foreach ($test->getChildren() as $child) {
            $this->markTestAsSkipped($child);
        }
    }

    public function markTestAsSkipped(Test $test)
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