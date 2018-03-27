<?php

declare(strict_types=1);

namespace Asynit\Output;

/**
 * Allow to detect current environment and choose the best output.
 */
class OutputFactory
{
    public function buildOutput(int $testCount): array
    {
        $countOutput = new Count();
        $chainOutput = new Chain();
        $chainOutput->addOutput(new PhpUnitAlike($testCount));
        $chainOutput->addOutput($countOutput);

        return [$chainOutput, $countOutput];
    }
}
