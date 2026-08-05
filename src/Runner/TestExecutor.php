<?php

namespace Asynit\Runner;

use PHPUnit\Event\Facade as EventFacade;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Runner\ShutdownHandler;
use PHPUnit\TextUI\Configuration\Registry as ConfigurationRegistry;

/**
 * Runs one test.
 *
 * This is what PHPUnit\Framework\TestRunner\TestRunner does, minus the parts that assume one test at a time.
 * Asynit calls TestCase::runBare() itself rather than TestCase::run(), which is what keeps this class in
 * asynit's own namespace instead of being yet another class overridden in PHPUnit's:
 *
 *  - TestCase::run() constructs `new TestRunner` inline, so the only way to replace it would be to take over
 *    PHPUnit\Framework\TestRunner\TestRunner wholesale;
 *  - the one thing run() does that we need, handleDependencies(), resolves PHPUnit's #[Depends] against the
 *    tests that already passed. Under concurrency that is a race, so asynit resolves those through its own
 *    graph instead and hands the values over with setDependencyInput().
 *
 * What is deliberately not carried over from PHPUnit's version:
 *
 *  - Runner\ErrorHandler, a singleton bound to a single test that asserts on !$this->enabled, so the second
 *    concurrent test would abort the run. ConcurrentRunner installs one error handler for the whole run.
 *  - code coverage, collected through another per test singleton.
 *
 * @internal
 */
final class TestExecutor
{
    public function execute(TestNode $node): void
    {
        $test = $node->test();

        // Asynit resolves the dependency graph itself and feeds the produced values in as the test method
        // arguments, which is the channel PHPUnit uses for its own #[Depends].
        $test->setDependencyInput($node->arguments());

        $assertionsBefore = Assert::getCount();
        $error = false;
        $incomplete = false;
        $skipped = false;

        ShutdownHandler::setMessage(sprintf('Fatal error: Premature end of PHP process when running %s.', $test->toString()));

        try {
            $test->runBare();
        } catch (AssertionFailedError $e) {
            $incomplete = $e instanceof \PHPUnit\Framework\IncompleteTest;
            $skipped = $e instanceof \PHPUnit\Framework\SkippedTest;
        } catch (\AssertionError) {
            $test->addToAssertionCount(1);
        } catch (\Throwable) {
            $error = true;
        } finally {
            ShutdownHandler::resetMessage();
        }

        $test->addToAssertionCount(max(0, Assert::getCount() - $assertionsBefore));

        $this->reportRiskyness($node, $error, $incomplete, $skipped);

        if ($test->hasUnexpectedOutput()) {
            EventFacade::emitter()->testPrintedUnexpectedOutput($test->output());
        }

        if ($test->wasPrepared()) {
            EventFacade::emitter()->testFinished($test->valueObjectForEvents(), $test->numberOfAssertionsPerformed());
        }
    }

    private function reportRiskyness(TestNode $node, bool $error, bool $incomplete, bool $skipped): void
    {
        $test = $node->test();
        $configuration = ConfigurationRegistry::get();

        // A producer only exists because something depends on the value it returns, so it is not held to the
        // "a test must assert something" rule the way a real test is.
        if ($configuration->reportUselessTests()
            && $node->isReported()
            && !$error && !$incomplete && !$skipped
            && !$test->doesNotPerformAssertions()
            && 0 === $test->numberOfAssertionsPerformed()) {
            EventFacade::emitter()->testConsideredRisky(
                $test->valueObjectForEvents(),
                'This test did not perform any assertions',
            );
        }

        if ($test->doesNotPerformAssertions() && $test->numberOfAssertionsPerformed() > 0) {
            EventFacade::emitter()->testConsideredRisky(
                $test->valueObjectForEvents(),
                sprintf(
                    'This test is not expected to perform assertions but performed %d assertion%s',
                    $test->numberOfAssertionsPerformed(),
                    $test->numberOfAssertionsPerformed() > 1 ? 's' : '',
                ),
            );
        }
    }
}
