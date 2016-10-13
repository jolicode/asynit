<?php

namespace Asynit\Output;

use Asynit\Test;
use Psr\Http\Message\RequestInterface;

/**
 * Interface for displaying tests
 */
interface OutputInterface
{
    public function outputPending(Test $test, RequestInterface $request);

    public function outputFailure(Test $test);

    public function outputSuccess(Test $test);
}
