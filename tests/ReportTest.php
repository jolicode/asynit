<?php

namespace Asynit\Tests;

use Amp\Process\Process;

use function Amp\ByteStream\buffer;

/**
 * PHPUnit's loggers track the running test in a single slot: interleaved tests must not see each other's.
 */
class ReportTest extends \PHPUnit\Framework\TestCase
{
    public function test_junit_report_attributes_each_outcome_to_its_test()
    {
        $report = tempnam(sys_get_temp_dir(), 'asynit');

        try {
            $process = Process::start(
                [
                    \PHP_BINARY,
                    __DIR__.'/../bin/asynit',
                    '--no-configuration',
                    '--bootstrap', __DIR__.'/../vendor/autoload.php',
                    '--log-junit', $report,
                    __DIR__.'/Fixtures/InterleavedOutcomesTest.php',
                ],
                environment: ['ASYNIT_CONCURRENCY' => '2'] + getenv(),
            );
            $output = buffer($process->getStdout());
            $process->join();

            $outcomes = [];

            foreach (simplexml_load_file($report)->xpath('//testcase') as $testCase) {
                $outcomes[(string) $testCase['name']] = match (true) {
                    isset($testCase->failure) => 'failure',
                    isset($testCase->error) => 'error',
                    default => 'success',
                };
            }

            ksort($outcomes);

            $this->assertSame([
                'test_errors_in_set_up_once_a_slot_frees' => 'error',
                'test_fails_while_another_test_runs' => 'failure',
                'test_passes_after_another_test_failed' => 'success',
            ], $outcomes, $output);
        } finally {
            unlink($report);
        }
    }
}
