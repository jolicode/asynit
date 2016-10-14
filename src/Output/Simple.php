<?php

namespace Asynit\Output;

use Asynit\Test;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class Simple implements OutputInterface
{
    /** @var OutputFormatterStyle */
    private $outputFormatFail;

    /** @var OutputFormatterStyle */
    private $outputFormatPending;

    /** @var OutputFormatterStyle */
    private $outputFormatSuccess;

    public function __construct()
    {
        $this->outputFormatFail = new OutputFormatterStyle('white', 'red', ['bold']);
        $this->outputFormatPending = new OutputFormatterStyle('black', 'yellow', ['bold']);
        $this->outputFormatSuccess = new OutputFormatterStyle('black', 'green', ['bold']);
    }

    public function outputStep(Test $test, $debugOutput)
    {
        $message = sprintf(
            "Step %s %s\n",
            $this->outputFormatPending->apply('Pending'),
            $test->getIdentifier()
        );

        fwrite(STDOUT, $message);
        fwrite(STDOUT, $debugOutput);
    }

    public function outputFailure(Test $test, $debugOutput, $failure)
    {
        $message = sprintf(
            "Step %s %s : %s\n",
            $this->outputFormatFail->apply('Failure'),
            $test->getIdentifier(),
            $failure->getMessage()
        );

        fwrite(STDOUT, $message);
        fwrite(STDOUT, $debugOutput);
    }

    public function outputSuccess(Test $test, $debugOutput)
    {
        $message = sprintf(
            "Step %s %s\n",
            $this->outputFormatSuccess->apply('Success'),
            $test->getIdentifier()
        );

        fwrite(STDOUT, $message);
        fwrite(STDOUT, $debugOutput);
    }
}
