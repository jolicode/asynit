<?php

namespace Asynit\Output;

use Asynit\Test;

class OutputOrder implements OutputInterface
{
    /** @var Test[] */
    private array $tests = [];

    public function outputStep(Test $test, string $debugOutput): void
    {
    }

    public function outputFailure(Test $test, string $debugOutput, ?\PHPUnit\Event\Code\Throwable $failure): void
    {
        $this->tests[] = $test;
    }

    public function outputSuccess(Test $test, string $debugOutput): void
    {
        $this->tests[] = $test;
    }

    public function outputSkipped(Test $test, string $debugOutput): void
    {
    }

    public function __destruct()
    {
        fwrite(STDOUT, "\nTest orders:\n\n");

        $orders = [];

        foreach ($this->tests as $index => $test) {
            $depends = $this->createDepends($test, $orders);
            $orders[$test->getDisplayName()] = $index;
            $dependsStr = '';

            if (\count($depends) > 0) {
                $dependsStr = ' depends on '.join(', ', array_map(function ($i) {
                    return '#'.$i;
                }, array_unique($depends)));
            }

            fwrite(STDOUT, ' - #'.$index.' '.$test->getDisplayName().$dependsStr."\n");
        }
    }

    /**
     * @param array<string, int>  $orders
     * @param array<string, true> $seen   guards against walking a dependency cycle forever
     *
     * @return int[]
     */
    public function createDepends(Test $test, array $orders = [], array &$seen = []): array
    {
        if (isset($seen[$test->getIdentifier()])) {
            return [];
        }

        $seen[$test->getIdentifier()] = true;
        $depends = [];

        foreach ($test->getParents() as $parentTest) {
            $depends = array_merge($depends, $this->createDepends($parentTest, $orders, $seen));
        }

        if (\array_key_exists($test->getDisplayName(), $orders)) {
            $depends[] = $orders[$test->getDisplayName()];
        }

        return $depends;
    }
}
