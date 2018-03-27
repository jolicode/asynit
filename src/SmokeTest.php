<?php

namespace Asynit;

class SmokeTest extends Test implements PoolAwareInterface
{
    private $pool;

    public function setPool(Pool $pool)
    {
        $this->pool = $pool;
    }

    public function getPool(): ?Pool
    {
        return $this->pool;
    }
}
