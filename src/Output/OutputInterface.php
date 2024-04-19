<?php

namespace Asynit\Output;

use Asynit\Test;

/**
 * Interface for displaying tests.
 *
 * @internal
 */
interface OutputInterface
{
    public function outputStep(Test $test, string $debugOutput): void;

    public function outputFailure(Test $test, string $debugOutput, \Throwable $failure): void;

    public function outputSuccess(Test $test, string $debugOutput): void;

    public function outputSkipped(Test $test, string $debugOutput): void;
}
