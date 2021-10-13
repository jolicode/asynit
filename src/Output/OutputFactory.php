<?php

declare(strict_types=1);

namespace Asynit\Output;

/**
 * Allow to detect current environment and choose the best output.
 */
class OutputFactory
{
    private $order = false;

    public function __construct(bool $order = false)
    {
        $this->order = $order;
    }

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
