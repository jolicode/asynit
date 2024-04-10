<?php

declare(strict_types=1);

namespace Asynit\Output;

use Asynit\Test;

class Chain implements OutputInterface
{
    /** @var OutputInterface[] */
    private $outputs = [];

    /**
     * Add output to the chain.
     */
    public function addOutput(OutputInterface $output)
    {
        $this->outputs[] = $output;
    }

    public function outputStep(Test $test, $debugOutput)
    {
        foreach ($this->outputs as $output) {
            $output->outputStep($test, $debugOutput);
        }
    }

    public function outputFailure(Test $test, $debugOutput, $failure)
    {
        foreach ($this->outputs as $output) {
            $output->outputFailure($test, $debugOutput, $failure);
        }
    }

    public function outputSuccess(Test $test, $debugOutput)
    {
        foreach ($this->outputs as $output) {
            $output->outputSuccess($test, $debugOutput);
        }
    }

    public function outputSkipped(Test $test, $debugOutput)
    {
        foreach ($this->outputs as $output) {
            $output->outputSkipped($test, $debugOutput);
        }
    }
}
