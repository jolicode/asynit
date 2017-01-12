<?php

declare(strict_types = 1);

namespace Asynit\Output;

use Asynit\Test;

class Chain implements OutputInterface
{
    /** @var OutputInterface[] */
    private $outputs = [];

    /**
     * Add output to the chain
     *
     * @param OutputInterface $output
     */
    public function addOutput(OutputInterface $output)
    {
        $this->outputs[] = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function outputStep(Test $test, $debugOutput)
    {
        foreach ($this->outputs as $output) {
            $output->outputStep($test, $debugOutput);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function outputFailure(Test $test, $debugOutput, $failure)
    {
        foreach ($this->outputs as $output) {
            $output->outputFailure($test, $debugOutput, $failure);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function outputSuccess(Test $test, $debugOutput)
    {
        foreach ($this->outputs as $output) {
            $output->outputSuccess($test, $debugOutput);
        }
    }
}
