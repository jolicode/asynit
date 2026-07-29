<?php

declare(strict_types=1);

namespace PHPUnit\Framework\TestRunner;

use PHPUnit\Event\Facade;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\IncompleteTestError;
use PHPUnit\Framework\SkippedTest;
use PHPUnit\Framework\TestCase;
use PHPUnit\TextUI\Configuration\Configuration;
use PHPUnit\TextUI\Configuration\Registry as ConfigurationRegistry;

/**
 * Drop-in replacement for PHPUnit's per test runner that is safe to use from concurrently running fibers.
 *
 * Two things in the original prevent tests from being in flight at the same time:
 *
 *  - PHPUnit\Runner\ErrorHandler is a singleton bound to a single test. It asserts on !$this->enabled when
 *    enabled, so the second concurrent test aborts the run, and it drives set_error_handler()/restore_error_handler(),
 *    a process wide LIFO stack that concurrent tests unwind out of order. Asynit installs a single error
 *    handler for the whole run instead, which turns PHP errors into exceptions the way the runner expects.
 *  - Code coverage is collected through another per test singleton, so it is only started when the run is
 *    sequential.
 *
 * What is intentionally NOT fixed here: Assert::$count is a private static counter, and the per test count is
 * a delta read around the test. Concurrent tests share that counter, so per test assertion counts are only
 * accurate with --concurrency 1. Making them correct needs Assert::$count to be fiber local, which is a five
 * line change to a 3300 line class we would rather not fork.
 *
 * @internal
 */
final class TestRunner
{
    private readonly Configuration $configuration;

    public function __construct()
    {
        $this->configuration = ConfigurationRegistry::get();
    }

    public function run(TestCase $test): void
    {
        $assertionsBefore = Assert::getCount();

        $error = false;
        $failure = false;
        $incomplete = false;
        $risky = false;
        $skipped = false;

        try {
            $test->runBare();
        } catch (AssertionFailedError $e) {
            $failure = true;

            if ($e instanceof IncompleteTestError) {
                $incomplete = true;
            } elseif ($e instanceof SkippedTest) {
                $skipped = true;
            }
        } catch (\AssertionError $e) {
            $test->addToAssertionCount(1);

            $failure = true;
        } catch (\Throwable $e) {
            $error = true;
        }

        $test->addToAssertionCount(max(0, Assert::getCount() - $assertionsBefore));

        if ($this->configuration->reportUselessTests()
            && !$test->doesNotPerformAssertions()
            && 0 === $test->numberOfAssertionsPerformed()) {
            $risky = true;
        }

        if (!$error && !$incomplete && !$skipped && $risky) {
            Facade::emitter()->testConsideredRisky(
                $test->valueObjectForEvents(),
                'This test did not perform any assertions',
            );
        }

        if ($test->doesNotPerformAssertions() && $test->numberOfAssertionsPerformed() > 0) {
            Facade::emitter()->testConsideredRisky(
                $test->valueObjectForEvents(),
                sprintf(
                    'This test is not expected to perform assertions but performed %d assertion%s',
                    $test->numberOfAssertionsPerformed(),
                    $test->numberOfAssertionsPerformed() > 1 ? 's' : '',
                ),
            );
        }

        if ($test->hasUnexpectedOutput()) {
            Facade::emitter()->testPrintedUnexpectedOutput($test->output());
        }

        if ($test->wasPrepared()) {
            Facade::emitter()->testFinished(
                $test->valueObjectForEvents(),
                $test->numberOfAssertionsPerformed(),
            );
        }
    }
}
