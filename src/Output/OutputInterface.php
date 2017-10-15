<?php

namespace Asynit\Output;

use Asynit\Test;

/**
 * Interface for displaying tests.
 */
interface OutputInterface
{
    public function outputStep(Test $test, $debugOutput);

    /**
     * @param Test                  $test
     * @param string                $debugOutput
     * @param \Throwable|\Exception $failure
     *
     * @return mixed
     */
    public function outputFailure(Test $test, $debugOutput, $failure);

    public function outputSuccess(Test $test, $debugOutput);

    public function outputSkipped(Test $test, $debugOutput);
}
