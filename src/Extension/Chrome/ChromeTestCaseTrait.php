<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use function Amp\call;
use Amp\Promise;

trait ChromeTestCaseTrait
{
    protected $target;

    public function setTarget(Target $target)
    {
        $this->target = $target;
    }

    private function getTarget(): Target
    {
        if ($this->target === null) {
            throw new \RuntimeException('No target available, please use the ChromeTab annotation to have it');
        }

        return $this->target;
    }

    public function createChromeClient(): Promise
    {
        return call(function () {
            return new Client(yield $this->getTarget()->createTab());
        });
    }
}
