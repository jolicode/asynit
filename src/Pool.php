<?php

namespace Asynit;

use Asynit\Runner\FutureHttp;
use Asynit\Runner\FutureHttpPool;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Pool containing test, running tests and running http calls.
 */
class Pool
{
    private $tests;

    private $runningTests;

    private $futureHttpPool;

    private $runningHttpPool;

    public function __construct()
    {
        $this->futureTests = new ArrayCollection();
        $this->runningTests = new ArrayCollection();
        $this->futureHttpPool = new FutureHttpPool();
        $this->runningHttpPool = new FutureHttpPool();
    }

    /**
     * Queue a test.
     *
     * @param Test $test
     */
    public function queueTest(Test $test)
    {
        $this->futureTests->add($test);
    }

    /**
     * Queue a list of future http.
     *
     * @param FutureHttp[] $futureHttpCollection
     */
    public function queueFutureHttp($futureHttpCollection)
    {
        $this->futureHttpPool->merge($futureHttpCollection);
    }

    /**
     * Pass a test in the queue to the running queue.
     *
     * @return Test|null
     */
    public function passRunningTest()
    {
        $test = $this->futureTests->first();

        if (false === $test) {
            return null;
        }

        $this->futureTests->removeElement($test);
        $this->runningTests->add($test);

        return $test;
    }

    /**
     * Pass a future http in the queue to the running one.
     *
     * @return FutureHttp|null
     */
    public function passRunningHttp()
    {
        $futureHttp = $this->futureHttpPool->first();

        if (false === $futureHttp) {
            return null;
        }

        $this->futureHttpPool->removeElement($futureHttp);
        $this->runningHttpPool->add($futureHttp);

        return $futureHttp;
    }

    /**
     * Set a test is over and remove him from the running queue.
     *
     * @param Test $test
     */
    public function passFinishTest(Test $test)
    {
        $this->runningTests->removeElement($test);
    }

    /**
     * Set an http call is over and remove him from the running queue.
     *
     * @param FutureHttp $futureHttp
     */
    public function passFinishHttp(FutureHttp $futureHttp)
    {
        $this->runningHttpPool->removeElement($futureHttp);
    }

    /**
     * Get the number of tests currently running.
     *
     * @return int
     */
    public function countRunningTest()
    {
        return $this->runningTests->count();
    }

    /**
     * Get the number of tests currently running.
     *
     * @return int
     */
    public function countRunningHttp()
    {
        return $this->runningHttpPool->count();
    }

    /**
     * Get the number of http calls currently pending.
     *
     * @return int
     */
    public function countPendingHttp()
    {
        return $this->futureHttpPool->count();
    }

    /**
     * Check if a test is in the pool.
     *
     * @param Test $test
     *
     * @return bool
     */
    public function hasTest(Test $test)
    {
        return $this->futureTests->contains($test) || $this->runningTests->contains($test);
    }

    /**
     * Check if the pool is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return
            $this->futureTests->isEmpty() &&
            $this->runningTests->isEmpty() &&
            $this->futureHttpPool->isEmpty() &&
            $this->runningHttpPool->isEmpty()
        ;
    }
}
