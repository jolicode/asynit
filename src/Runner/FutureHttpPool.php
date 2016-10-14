<?php

namespace Asynit\Runner;

use Doctrine\Common\Collections\ArrayCollection;

class FutureHttpPool extends ArrayCollection
{
    /**
     * Shift a future http of the beggining.
     *
     * @return FutureHttp
     */
    public function shift()
    {
        $futureHttp = $this->first();
        $this->removeElement($futureHttp);

        return $futureHttp;
    }

    /**
     * Flush the pool and return all elements in it.
     *
     * @return FutureHttp[]
     */
    public function flush()
    {
        $tests = $this->toArray();
        $this->clear();

        return $tests;
    }

    /**
     * Merge a collection of future http into the current pool.
     *
     * @param FutureHttp[] $futureHttpCollection
     */
    public function merge($futureHttpCollection)
    {
        foreach ($futureHttpCollection as $futureHttp) {
            $this->add($futureHttp);
        }
    }
}
