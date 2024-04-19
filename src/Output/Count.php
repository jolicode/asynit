<?php

declare(strict_types=1);

namespace Asynit\Output;

use Asynit\Test;

class Count implements OutputInterface
{
    private int $succeed = 0;
    private int $failed = 0;
    private int $skipped = 0;

    public function outputStep(Test $test, string $debugOutput): void
    {
    }

    public function outputFailure(Test $test, string $debugOutput, \Throwable $failure): void
    {
        ++$this->failed;
    }

    public function outputSuccess(Test $test, string $debugOutput): void
    {
        ++$this->succeed;
    }

    public function outputSkipped(Test $test, string $debugOutput): void
    {
        ++$this->skipped;
    }

    public function getSucceed(): int
    {
        return $this->succeed;
    }

    public function getFailed(): int
    {
        return $this->failed;
    }
}
