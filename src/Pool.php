<?php

namespace Asynit;

/**
 * Pool containing test, running tests and running http calls.
 *
 * @internal
 */
final class Pool
{
    /** @var Test[] */
    public array $tests = [];

    public function isEmpty(): bool
    {
        $notCompletedTests = array_filter($this->tests, function (Test $test) {
            return !$test->isCompleted();
        });

        return 0 === count($notCompletedTests);
    }

    public function getNextTestToRun(): ?Test
    {
        foreach ($this->tests as $test) {
            if ($test->canBeRun()) {
                return $test;
            }
        }

        return null;
    }
}
