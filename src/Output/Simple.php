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
            "%s %s",
            $this->outputFormatPending->apply('Pending'),
            $test->getIdentifier()
        );

        $this->outputMessage($test, $message, $debugOutput, true);
    }

    public function outputFailure(Test $test, $debugOutput, $failure)
    {
        $message = sprintf(
            "%s %s : %s",
            $this->outputFormatFail->apply('Failure'),
            $test->getIdentifier(),
            $failure->getMessage()
        );

        $this->outputMessage($test, $message, $debugOutput);
    }

    public function outputSuccess(Test $test, $debugOutput)
    {
        $message = sprintf(
            "%s %s",
            $this->outputFormatSuccess->apply('Success'),
            $test->getIdentifier()
        );

        $this->outputMessage($test, $message, $debugOutput);
    }

    protected function outputMessage(Test $test, $message, $debugOutput, $temp = false)
    {
        if (!$temp) {
            fwrite(STDOUT, $message . "\n");
            fwrite(STDOUT, $debugOutput);
        }
    }
}
