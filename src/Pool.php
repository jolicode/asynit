<?php

namespace Asynit;

/**
 * Pool containing test, running tests and running http calls.
 */
class Pool
{
    /** @var Test[] */
    private $tests;

    public function __construct()
    {
        $this->tests = [];
    }

    /**
     * Queue a test.
     */
    public function addTest(Test $test): void
    {
        $this->tests[] = $test;
    }

    public function isEmpty(): bool
    {
        $notCompletedTests = array_filter($this->tests, function (Test $test) {
            return !$test->isCompleted();
        });

        return 0 === count($notCompletedTests);
    }

    public function getTestToRun(): ?Test
    {
        foreach ($this->tests as $test) {
            if ($test->canBeRun()) {
                return $test;
            }
        }

        return null;
    }
}
