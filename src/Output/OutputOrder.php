<?php

namespace Asynit\Output;

use Asynit\Test;

class OutputOrder implements OutputInterface
{
    /** @var Test[] */
    private $tests = [];

    public function outputStep(Test $test, $debugOutput)
    {
    }

    public function outputFailure(Test $test, $debugOutput, $failure)
    {
        $this->tests[] = $test;
    }

    public function outputSuccess(Test $test, $debugOutput)
    {
        $this->tests[] = $test;
    }

    public function outputSkipped(Test $test, $debugOutput)
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

    public function createDepends(Test $test, array $orders = []): array
    {
        $depends = [];

        foreach ($test->getParents() as $parentTest) {
            $depends = array_merge($depends, $this->createDepends($parentTest, $orders));
        }

        if (\array_key_exists($test->getDisplayName(), $orders)) {
            $depends[] = $orders[$test->getDisplayName()];
        }

        return $depends;
    }
}
