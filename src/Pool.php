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
     *
     * @param Test $test
     */
    public function addTest(Test $test)
    {
        $this->tests[] = $test;
    }

    public function isEmpty(): bool
    {
        foreach ($this->tests as $test) {
            if (!$test->isCompleted()) {
                return false;
            }
        }

        return true;
    }

    public function getTests()
    {
        return $this->tests;
    }

    public function getTestToRun()
    {
        foreach ($this->tests as $test) {
            if ($test->canBeRun()) {
                return $test;
            }
        }

        return null;
    }
}
