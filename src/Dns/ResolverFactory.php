<?php

declare(strict_types=1);

namespace Asynit\Dns;

use React\Dns\Query\RetryExecutor;
use React\Dns\Resolver\Factory;
use React\EventLoop\LoopInterface;

class ResolverFactory extends Factory
{
    protected function createRetryExecutor(LoopInterface $loop)
    {
        $retryExecutor = new RetryExecutor($this->createExecutor($loop));

        return new HostsFileExecutor($loop, $retryExecutor);
    }
}
