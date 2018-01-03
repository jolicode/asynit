<?php

namespace Asynit\Runner;

use Amp\Deferred;
use Amp\Sync\Lock;
use Amp\Sync\Semaphore;
use Amp\Promise;

/**
 * @see https://gist.github.com/kelunik/8205a94dfc4d2f11e63a993dabef97cd
 */
class SimpleSemaphore implements Semaphore
{
    private $queue = [];
    private $locks = 0;
    private $maxConcurrent;

    public function __construct(int $maxConcurrent = 1)
    {
        $this->maxConcurrent = $maxConcurrent;
    }

    /**
     * {@inheritdoc}
     */
    public function acquire(): Promise
    {
        $deferred = new Deferred();
        if ($this->locks < $this->maxConcurrent) {
            $deferred->resolve($this->createLock());
        } else {
            $this->queue[] = $deferred;
        }
        return $deferred->promise();
    }

    private function createLock()
    {
        ++$this->locks;

        return new Lock(0, function () {
            --$this->locks;

            if ($this->queue) {
                $deferred = array_shift($this->queue);
                $deferred->resolve($this->createLock());
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->maxConcurrent - $this->locks;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): int
    {
        return $this->maxConcurrent;
    }
}
