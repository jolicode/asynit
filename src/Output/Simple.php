<?php

namespace Asynit\Output;

use Asynit\Test;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class Simple implements OutputInterface
{
    private $formatter;

    public function __construct()
    {
        $this->formatter = new Formatter();
    }

    public function outputStep(Test $test, $debugOutput)
    {
        $this->outputMessage($test, $this->formatter->formatStepTest($test), $debugOutput, true);
    }

    public function outputFailure(Test $test, $debugOutput, $failure)
    {
        $this->outputMessage($test, $this->formatter->formatFailedTest($test, $failure), $debugOutput);
    }

    public function outputSuccess(Test $test, $debugOutput)
    {
        $this->outputMessage($test, $this->formatter->formatSuccessTest($test), $debugOutput);
    }

    protected function outputMessage(Test $test, $message, $debugOutput, $temp = false)
    {
        if (!$temp) {
            fwrite(STDOUT, $message);
            fwrite(STDOUT, $debugOutput);
        }
    }
}
