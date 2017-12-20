<?php

declare(strict_types=1);

namespace Asynit;

use Asynit\Output\Chain;
use Asynit\Output\Count;
use Asynit\Output\Detector;

class Factory
{
    /**
     * @param null $forceTty
     * @param null $forceNoTty
     *
     * @return array
     */
    public static function createOutput($forceTty = null, $forceNoTty = null)
    {
        $countOutput = new Count();
        $chainOutput = new Chain();
        $chainOutput->addOutput((new Detector())->detect($forceTty, $forceNoTty));
        $chainOutput->addOutput($countOutput);

        return [$chainOutput, $countOutput];
    }
}
