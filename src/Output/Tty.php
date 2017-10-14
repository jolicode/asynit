<?php

namespace Asynit\Output;

use Amp\Loop;
use Asynit\Test;

class Tty extends Simple
{
    /** @var int */
    private $rows = 30;

    /** @var int */
    private $columns = 80;

    /** @var TestOutput[] */
    private $testOutputs = [];

    public function __construct()
    {
        parent::__construct();

        $this->setTerminalSize();

        if (\extension_loaded("pcntl")) {
            Loop::onSignal(SIGWINCH, function () {
                $this->setTerminalSize();
            });
        }
    }

    /**
     * Set the terminal size by using stty -a
     */
    protected function setTerminalSize()
    {
        exec('stty -a', $sttyOutput);

        preg_match('/rows (\d+); columns (\d+)/', $sttyOutput[0], $matches);

        if (count($matches) < 2) {
            preg_match('/(\d+) rows; (\d+) columns/', $sttyOutput[0], $matches);
        }

        $this->rows = $matches[1];
        $this->columns = $matches[2];
    }

    protected function outputMessage(Test $test, $message, $debugMessage, $temp = false)
    {
        if (!array_key_exists($test->getIdentifier(), $this->testOutputs)) {
            $this->testOutputs[$test->getIdentifier()] = new TestOutput(count($this->testOutputs));
        }

        /** @var TestOutput $testOutput */
        $testOutput = $this->testOutputs[$test->getIdentifier()];
        $testOutput->addDebugOutput($debugMessage);
        $testOutput->setMessage($message);

        // Calculate size to go up
        $upSize = 0;

        foreach ($this->testOutputs as $testOutputItem) {
            if ($testOutputItem->getIndex() >= $testOutput->getIndex()) {
                $upSize += $testOutputItem->getLastOutputSize();
            }
        }

        // If size to go up is higher than the current columns count print this test to the bottom
        if ($upSize > $this->rows) {
            // Decrement all next index
            foreach ($this->testOutputs as $testOutputItem) {
                if ($testOutputItem->getIndex() > $testOutput->getIndex()) {
                    $testOutputItem->decrementIndex();
                }
            }

            // Change the index to the last one
            $index = count($this->testOutputs) - 1;
            $upSize = 0;
            $this->testOutputs[$test->getIdentifier()]->setIndex($index);
        }

        // Go up for X lines
        if ($upSize > 0) {
            fwrite(STDOUT, sprintf("\e[%sA", $upSize));
        }

        $this->draw($testOutput->getIndex() - 1);
    }

    protected function draw($index)
    {
        foreach ($this->testOutputs as $testOutput) {
            if ($testOutput->getIndex() > $index) {
                $size = $testOutput->calculateHeightSize($this->columns);
                $this->drawTest($testOutput);
                $testOutput->setLastOutputSize($size);
            }
        }
    }

    protected function drawTest($testOutput)
    {
        $lines = $testOutput->getOutput();

        foreach ($lines as $key => $debugOutput) {
            fwrite(STDOUT, "\r\e[K" . $debugOutput);

            if (count($lines) > $key + 1) {
                fwrite(STDOUT, "\n");
            }
        }
    }
}
