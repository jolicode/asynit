<?php

namespace Asynit\Output;

use Asynit\Test;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class PhpUnitAlike implements OutputInterface
{
    public const SPLIT_AT = 60;
    public const MAX_TRACE = 10;

    private OutputFormatterStyle $outputFormatFail;
    private OutputFormatterStyle $outputFormatSuccess;
    private OutputFormatterStyle $outputFormatSkipped;
    private int $testOutputed;
    /** @var array<array{test: Test, failure: ?\PHPUnit\Event\Code\Throwable}> */
    private array $failures;
    private int $assertionCount;
    private float $start;
    private int $testCount;

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

    public function outputStep(Test $test, string $debugOutput): void
    {
    }

    public function outputFailure(Test $test, string $debugOutput, ?\PHPUnit\Event\Code\Throwable $failure): void
    {
        $text = $test->failureIsAssertion ? 'F' : 'E';

        $this->writeTest($test, $this->outputFormatFail->apply($text));
        fwrite(STDOUT, $debugOutput);

        $this->assertionCount += $test->getAssertionsCount();

        $this->failures[] = [
            'test' => $test,
            'failure' => $failure,
        ];
    }

    public function outputSuccess(Test $test, string $debugOutput): void
    {
        $this->writeTest($test, $this->outputFormatSuccess->apply('.'));
        fwrite(STDOUT, $debugOutput);

        $this->assertionCount += $test->getAssertionsCount();
    }

    public function outputSkipped(Test $test, string $debugOutput): void
    {
        $this->writeTest($test, $this->outputFormatSkipped->apply('S'));
        fwrite(STDOUT, $debugOutput);

        $this->assertionCount += $test->getAssertionsCount();
    }

    private function writeTest(Test $test, string $text): void
    {
        if (!$test->isRealTest) {
            return;
        }

        if (0 !== $this->testOutputed && 0 === ($this->testOutputed % self::SPLIT_AT)) {
            $testDone = round(($this->testOutputed * 100) / $this->testCount);
            fwrite(STDOUT, " $this->testOutputed / $this->testCount ($testDone%)\n");
        }

        fwrite(STDOUT, $text);

        ++$this->testOutputed;
    }

    private function writeFailure(int $step, Test $test, ?\PHPUnit\Event\Code\Throwable $failure): void
    {
        fwrite(STDOUT, $step + 1 .') '.$test->getDisplayName()." failed\n\n");

        if (null === $failure) {
            fwrite(STDOUT, "No details available.\n\n");

            return;
        }

        fwrite(STDOUT, $failure->className().': '.$failure->message()."\n\n");

        // PHPUnit hands the stack trace over already formatted and filtered.
        $trace = array_slice(explode("\n", trim($failure->stackTrace())), 0, self::MAX_TRACE);

        foreach ($trace as $line) {
            fwrite(STDOUT, $line."\n");
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

    private function getDisplayableMemory(int $memory): string
    {
        $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        if (($memory - 0.1) < 0) {
            return '0 '.$unit[0];
        }

        return @round($memory / (1000 ** $i = (int) floor(log($memory, 1000))), 2).($unit[$i] ?? 'B');
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
