<?php

declare(strict_types=1);

namespace Asynit\Output;

use Asynit\Test;

class Count implements OutputInterface
{
    private $succeed = 0;
    private $failed = 0;
    private $skipped = 0;

    public function outputStep(Test $test, $debugOutput)
    {
    }

    public function outputFailure(Test $test, $debugOutput, $failure)
    {
        ++$this->failed;
    }

    public function outputSuccess(Test $test, $debugOutput)
    {
        ++$this->succeed;
    }

    public function outputSkipped(Test $test, $debugOutput)
    {
        ++$this->skipped;
    }

    /**
     * @return int
     */
    public function getSucceed()
    {
        return $this->succeed;
    }

    /**
     * @return int
     */
    public function getFailed()
    {
        return $this->failed;
    }
}
