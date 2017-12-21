<?php

declare(strict_types=1);

namespace Asynit\Output;

use Asynit\Test;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class Formatter
{
    private $outputFormatFail;
    private $outputFormatPending;
    private $outputFormatSuccess;
    private $outputFormatSkipped;

    public function __construct()
    {
        $this->outputFormatFail = new OutputFormatterStyle('white', 'red', ['bold']);
        $this->outputFormatPending = new OutputFormatterStyle('black', 'yellow', ['bold']);
        $this->outputFormatSuccess = new OutputFormatterStyle('black', 'green', ['bold']);
        $this->outputFormatSkipped = new OutputFormatterStyle('black', 'cyan', ['bold']);
    }

    public function formatStepTest(Test $test)
    {
        return sprintf(
            "%s %s%s\n",
            $this->outputFormatPending->apply('Running'),
            $test->getDisplayName(),
            $this->createAssertionMessage($test)
        );
    }

    public function formatFailedTest(Test $test, \Throwable $failure)
    {
        return sprintf(
            "%s %s\n\t\u{2715} %s%s\n",
            $this->outputFormatFail->apply('Failure'),
            $test->getDisplayName(),
            $failure->getMessage(),
            $this->createAssertionMessage($test)
        );
    }

    public function formatSuccessTest(Test $test)
    {
        return sprintf(
            "%s %s%s\n",
            $this->outputFormatSuccess->apply('Success'),
            $test->getDisplayName(),
            $this->createAssertionMessage($test)
        );
    }

    public function formatSkippedTest(Test $test)
    {
        return sprintf(
            "%s %s%s\n",
            $this->outputFormatSkipped->apply('Skipped'),
            $test->getDisplayName(),
            $this->createAssertionMessage($test)
        );
    }

    private function createAssertionMessage(Test $test)
    {
        $text = '';

        foreach ($test->getAssertions() as $assertion) {
            $text .= sprintf("\n\t\u{2714} %s", $assertion);
        }

        return $text;
    }
}
