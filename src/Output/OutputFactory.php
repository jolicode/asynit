<?php

declare(strict_types=1);

namespace Asynit\Output;

/**
 * Allow to detect current environment and choose the best output.
 */
class OutputFactory
{
    public function buildOutput(bool $forceTty = null, bool $forceNoTty = null): array
    {
        $countOutput = new Count();

        $chainOutput = new Chain();
        $bestOutput = $this->buildBestOutput($forceTty, $forceNoTty);
        $chainOutput->addOutput($bestOutput);
        $chainOutput->addOutput($countOutput);

        return [$chainOutput, $countOutput];
    }

    private function buildBestOutput(bool $forceTty = null, bool $forceNoTty = null): OutputInterface
    {
        if ($forceTty) {
            return new Tty();
        }

        if ($forceNoTty) {
            return new Simple();
        }

        // Return simple output if no posix methods
        if (!function_exists('posix_isatty')) {
            return new Simple();
        }

        // Return simple output if not tty
        if (!posix_isatty(STDOUT)) {
            return new Simple();
        }

        // Return tty output when STDOUT is a tty
        return new Tty();
    }
}
