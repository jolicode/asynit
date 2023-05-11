<?php

namespace Asynit\Output;

use Asynit\Test;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class PhpUnitAlike implements OutputInterface
{
    public const SPLIT_AT = 60;
    public const MAX_TRACE = 10;

    private $outputFormatFail;
    private $outputFormatSuccess;
    private $outputFormatSkipped;
    private $testOutputed;
    private $failures;
    private $assertionCount;
    private $start;
    private $testCount;

    public function __construct(int $testCount)
    {
        $this->testCount = $testCount;
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

        $this->writeTest($test, $this->outputFormatFail->apply($text));
        fwrite(STDOUT, $debugOutput);

        $this->assertionCount += \count($test->getAssertions());

        $this->failures[] = [
            'test' => $test,
            'failure' => $failure,
        ];
    }

    public function outputSuccess(Test $test, $debugOutput)
    {
        $this->writeTest($test, $this->outputFormatSuccess->apply('.'));
        fwrite(STDOUT, $debugOutput);

        $this->assertionCount += \count($test->getAssertions());
    }

    public function outputSkipped(Test $test, $debugOutput)
    {
        $this->writeTest($test, $this->outputFormatSkipped->apply('S'));
        fwrite(STDOUT, $debugOutput);

        $this->assertionCount += \count($test->getAssertions());
    }

    private function writeTest(Test $test, $text)
    {
        if (!$test->isRealTest()) {
            return;
        }

        if (0 !== $this->testOutputed && 0 === ($this->testOutputed % self::SPLIT_AT)) {
            $testDone = round(($this->testOutputed * 100) / $this->testCount);
            fwrite(STDOUT, " $this->testOutputed / $this->testCount ($testDone%)\n");
        }

        fwrite(STDOUT, $text);

        ++$this->testOutputed;
    }

    private function writeFailure($step, Test $test, $failure)
    {
        fwrite(STDOUT, $step + 1 .') '.$test->getDisplayName()." failed\n");

        if ($failure instanceof \Throwable) {
            fwrite(STDOUT, "\n");
            fwrite(STDOUT, get_class($failure).': '.$failure->getMessage().' at '.$failure->getFile().':'.$failure->getLine()."\n");
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
        $outputFormatFail = new OutputFormatterStyle('black', 'red');
        $outputFormatSuccess = new OutputFormatterStyle('black', 'green');

        $spaceLeft = (self::SPLIT_AT - ($this->testOutputed % self::SPLIT_AT));
        fwrite(STDOUT, str_pad(" $this->testOutputed / $this->testCount (100%)\n", $spaceLeft, ' ', STR_PAD_LEFT));

        $time = microtime(true) - $this->start;
        $time = round($time * 1000, 2);
        $time = $this->getDisplayableTime($time);
        $memory = $this->getDisplayableMemory(memory_get_peak_usage());

        if (\count($this->failures) > 0) {
            fwrite(STDOUT, "\n# Failures:\n\n");

            foreach ($this->failures as $step => $failure) {
                $this->writeFailure($step, $failure['test'], $failure['failure']);
            }

            fwrite(STDOUT, "\nTime: $time, $memory\n\n");
            fwrite(STDOUT, $outputFormatFail->apply("Failed, Tests: $this->testOutputed, Assertions: $this->assertionCount.\n"));

            return;
        }

        fwrite(STDOUT, "\nTime: $time, Memory: $memory\n\n");
        fwrite(STDOUT, $outputFormatSuccess->apply("OK, Tests: $this->testOutputed, Assertions: $this->assertionCount.\n"));
    }

    private function getDisplayableMemory($memory)
    {
        $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        if (($memory - 0.1) < 0) {
            return '0 '.$unit[0];
        }

        return @round($memory / pow(1000, $i = floor(log($memory, 1000))), 2).(isset($unit[$i]) ? $unit[$i] : 'B');
    }

    private function getDisplayableTime(float $time): string
    {
        $milliseconds = $time;
        $seconds = (intval($time) % 60000) / 1000;
        $minutes = intval($time / (1000 * 60)) % 60;
        $hours = $time / (1000 * 60 * 60);

        $text = sprintf('%.2F milliseconds', $milliseconds);

        if (($seconds - 0.1) < 0) {
            return $text;
        }

        $text = sprintf('%.2F seconds', $seconds);

        if (($minutes - 0.1) < 0) {
            return $text;
        }

        $text = sprintf('%d minutes, %s', $minutes, $text);

        if (($hours - 0.1) < 0) {
            return $text;
        }

        return sprintf('%d hours, %s', $hours, $text);
    }
}
