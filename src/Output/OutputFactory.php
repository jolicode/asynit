<?php

declare(strict_types=1);

namespace Asynit\Output;

/**
 * Allow to detect current environment and choose the best output.
 *
 * @internal
 */
final class OutputFactory
{
    public function __construct(public readonly bool $order = false)
    {
    }

    /**
     * @return array{Chain, Count}
     */
    public function buildOutput(int $testCount): array
    {
        $countOutput = new Count();
        $chainOutput = new Chain();
        $chainOutput->addOutput(new PhpUnitAlike($testCount));
        $chainOutput->addOutput($countOutput);

        if ($this->order) {
            $chainOutput->addOutput(new OutputOrder());
        }

        return [$chainOutput, $countOutput];
    }
}
