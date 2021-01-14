<?php

namespace Asynit;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pool containing test, running tests and running http calls.
 */
class Pool
{
    /** @var Test[]|ArrayCollection */
    private $tests;

    public function __construct()
    {
        $this->tests = new ArrayCollection();
    }

    /**
     * Queue a test.
     */
    public function addTest(Test $test)
    {
        $this->tests->add($test);

        if ($test instanceof PoolAwareInterface) {
            $test->setPool($this);
        }
    }

    public function isEmpty(): bool
    {
        return 0 === $this->tests->filter(function (Test $test) {
            return !$test->isCompleted();
        })->count();
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
