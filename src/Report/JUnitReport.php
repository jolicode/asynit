<?php

namespace Asynit\Report;

use Asynit\Test;
use Asynit\TestSuite;
use bovigo\assert\AssertionFailure;

final class JUnitReport
{
    public function __construct(
        private readonly string $filename,
    ) {
    }

    /**
     * @param TestSuite<object>[] $testSuites
     */
    public function generate(float $time, array $testSuites): void
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $root = $xml->createElement('testsuites');
        $root->setAttribute('name', 'asynit');

        $totalTests = 0;
        $totalFailures = 0;
        $totalErrors = 0;
        $totalSuccess = 0;
        $totalSKipped = 0;
        $totalAssertions = 0;

        /** @var TestSuite<object> $testSuite */
        foreach ($testSuites as $testSuite) {
            $testsCount = count($testSuite->tests);

            if (0 === $testsCount) {
                continue;
            }

            $failures = $testSuite->getFailure();
            $errors = $testSuite->getErrors();
            $success = $testSuite->getSuccess();
            $skipped = $testSuite->getSkipped();
            $assertions = $testSuite->getAssertions();

            $totalTests += $testsCount;
            $totalFailures += $failures;
            $totalErrors += $errors;
            $totalSuccess += $success;
            $totalSKipped += $skipped;
            $totalAssertions += $assertions;

            $testsuites = $xml->createElement('testsuite');
            $testsuites->setAttribute('name', $testSuite->reflectionClass->getName());
            $testsuites->setAttribute('tests', (string) $testsCount);
            $testsuites->setAttribute('failures', (string) $failures);
            $testsuites->setAttribute('errors', (string) $errors);
            $testsuites->setAttribute('skipped', (string) $skipped);
            $testsuites->setAttribute('assertions', (string) $assertions);
            $testsuites->setAttribute('time', (string) $testSuite->getTime());
            // timestamp in ISO 8601 format
            $date = \DateTime::createFromFormat('U.u', (string) $testSuite->startTime);

            if ($date) {
                $testsuites->setAttribute('timestamp', $date->format(\DateTimeInterface::ISO8601_EXPANDED));
            }

            $testsuites->setAttribute('file', (string) $testSuite->reflectionClass->getFileName());
            $root->appendChild($testsuites);

            /** @var Test $test */
            foreach ($testSuite->tests as $test) {
                $testcase = $xml->createElement('testcase');
                $testcase->setAttribute('name', $test->getDisplayName());
                $testcase->setAttribute('classname', $testSuite->reflectionClass->getName());
                $testcase->setAttribute('assertions', (string) $test->getAssertionsCount());
                $testcase->setAttribute('time', (string) $test->getTime());
                $testcase->setAttribute('file', (string) $testSuite->reflectionClass->getFileName());
                $testcase->setAttribute('line', (string) $test->method->getStartLine());
                $date = \DateTime::createFromFormat('U.u', (string) $test->startTime);

                if ($date) {
                    $testcase->setAttribute('timestamp', $date->format(\DateTimeInterface::ISO8601_EXPANDED));
                }

                $testsuites->appendChild($testcase);

                if ('' !== $test->output) {
                    $systemOut = $xml->createElement('system-out');
                    $systemOut->appendChild($xml->createCDATASection($test->output));
                    $testcase->appendChild($systemOut);
                }

                if (Test::STATE_FAILURE === $test->state) {
                    if ($test->failure instanceof AssertionFailure) {
                        $failure = $xml->createElement('failure');
                        $failure->setAttribute('message', $test->failure->getMessage());
                        $failure->setAttribute('type', get_class($test->failure));

                        $testcase->appendChild($failure);
                    } else {
                        $failure = $xml->createElement('error');
                        $failure->setAttribute('message', $test->failure->getMessage());
                        $failure->setAttribute('type', get_class($test->failure));

                        $testcase->appendChild($failure);
                    }
                }

                if (Test::STATE_SKIPPED === $test->state) {
                    $skipped = $xml->createElement('skipped');
                    $testcase->appendChild($skipped);
                }
            }
        }

        $directory = dirname($this->filename);

        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $root->setAttribute('tests', (string) $totalTests);
        $root->setAttribute('failures', (string) $totalFailures);
        $root->setAttribute('errors', (string) $totalErrors);
        $root->setAttribute('skipped', (string) $totalSKipped);
        $root->setAttribute('assertions', (string) $totalAssertions);
        $root->setAttribute('time', (string) $time);

        $xml->appendChild($root);

        $xml->save($this->filename);
    }
}
