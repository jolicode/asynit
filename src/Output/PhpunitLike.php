<?php

namespace Asynit\Output;

use Asynit\Test;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class PhpunitLike implements OutputInterface
{
    const SPLIT_AT = 60;
    const MAX_TRACE = 10;

    private $outputFormatFail;
    private $outputFormatSuccess;
    private $outputFormatSkipped;
    private $testOutputed;
    private $failures;
    private $assertionCount;

    public function __construct()
    {
        $this->outputFormatFail = new OutputFormatterStyle('red', null, ['bold']);
        $this->outputFormatSuccess = new OutputFormatterStyle('default', null, ['bold']);
        $this->outputFormatSkipped = new OutputFormatterStyle('cyan', null, ['bold']);

        fwrite(STDOUT, "Asynit Test suite\n\n");

        $this->testOutputed = 0;
        $this->assertionCount = 0;
        $this->start = microtime(true);
        $this->failures = [];
    }

    public function outputStep(Test $test, $debugOutput)
    {
    }

    public function outputFailure(Test $test, $debugOutput, $failure)
    {
        $text = 'F';

        if ($failure instanceof \Error || $failure instanceof \ErrorException) {
            $text = 'E';
        }

        $this->writeTest($this->outputFormatFail->apply($text));
        fwrite(STDOUT, $debugOutput);

        $this->assertionCount += \count($test->getAssertions());

        $this->failures[] = [
            'test' => $test,
            'failure' => $failure
        ];
    }

    public function outputSuccess(Test $test, $debugOutput)
    {
        $this->writeTest($this->outputFormatSuccess->apply('.'));
        fwrite(STDOUT, $debugOutput);

        $this->assertionCount += \count($test->getAssertions());
    }

    public function outputSkipped(Test $test, $debugOutput)
    {
        $this->writeTest($this->outputFormatSkipped->apply('S'));
        fwrite(STDOUT, $debugOutput);

        $this->assertionCount += \count($test->getAssertions());
    }

    private function writeTest($text)
    {
        if ($this->testOutputed !== 0 && ($this->testOutputed % self::SPLIT_AT) === 0) {
            fwrite(STDOUT, " [$this->testOutputed]\n");
        }

        fwrite(STDOUT, $text);

        ++$this->testOutputed;
    }

    private function writeFailure($step, Test $test, $failure)
    {
        fwrite(STDOUT, $step + 1 . ") " . $test->getDisplayName() . " failed\n");

        if ($failure instanceof \Throwable) {
            fwrite(STDOUT, "\n");
            fwrite(STDOUT, get_class($failure) . ": " . $failure->getMessage() . " at " . $failure->getFile() . ":" . $failure->getLine() . "\n");
            fwrite(STDOUT, "\n");
            $trace = $failure->getTrace();

            for ($i = 0, $count = min(\count($trace), self::MAX_TRACE); $i < $count; ++$i) {
                $class = isset($trace[$i]['class']) ? $trace[$i]['class'] : '';
                $type = isset($trace[$i]['type']) ? $trace[$i]['type'] : '';
                $function = $trace[$i]['function'];
                $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : 'n/a';
                $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : 'n/a';

                fwrite(STDOUT, sprintf("#%s %s%s%s() at %s:%s\n", $i, $class, $type, $function, $file, $line));
            }
        }

        fwrite(STDOUT, "\n");
    }

    public function __destruct()
    {
        $time = microtime(true) - $this->start;
        $time = round($time * 1000, 2);

        fwrite(STDOUT, "\n\n# Failures:\n\n");

        foreach ($this->failures as $step => $failure) {
            $this->writeFailure($step, $failure['test'], $failure['failure']);
        }

        fwrite(STDOUT, "\n\nExecuted $this->testOutputed tests, $this->assertionCount assertions in $time ms\n");
    }

}
