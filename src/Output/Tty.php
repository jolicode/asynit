<?php

namespace Asynit\Output;

use Asynit\Test;
use MKraemer\ReactPCNTL\PCNTL;
use React\EventLoop\LoopInterface;

class Tty extends Simple
{
    /** @var LoopInterface  */
    private $loop;

    /** @var int */
    private $rows;

    /** @var int */
    private $columns;

    /** @var array */
    private $testOutputs = [];

    public function __construct(LoopInterface $loop)
    {
        parent::__construct();

        $this->loop = $loop;
        $this->setTerminalSize();

        if (function_exists('pcntl_signal')) {
            $pcntl = new PCNTL($loop);
            $pcntl->on(SIGWINCH, function () {
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

        $this->rows = $matches[1];
        $this->columns = $matches[2];
    }

    protected function outputMessage(Test $test, $message, $debugMessage, $temp = false)
    {
        if (!array_key_exists($test->getIdentifier(), $this->testOutputs)) {
            $this->testOutputs[$test->getIdentifier()] = [
                'output' => new TestOutput(),
                'index' => count($this->testOutputs),
            ];
        }

        /** @var TestOutput $testOutput */
        $index = $this->testOutputs[$test->getIdentifier()]['index'];
        $testOutput = $this->testOutputs[$test->getIdentifier()]['output'];
        $testOutput->addDebugOutput($debugMessage);
        $testOutput->setMessage($message);

        $size = $testOutput->calculateHeightSize($this->columns);
        // Calculate size to go up
        $upSize = $testOutput->getLastOutputSize();

        foreach ($this->testOutputs as $testOutputIndex) {
            if ($testOutputIndex['index'] > $index) {
                $upSize += $testOutputIndex['output']->getLastOutputSize();
            }
        }

        // If size to go up is higher than the current columns count print this test to the bottom
        if ($upSize > $this->rows) {
            // Decrement all next index
            foreach ($this->testOutputs as &$testOutputIndex) {
                if ($testOutputIndex['index'] > $index) {
                    $testOutputIndex['index']--;
                }
            }

            // Change the index to the last one
            $index = count($this->testOutputs) - 1;
            $upSize = 0;
            $this->testOutputs[$test->getIdentifier()]['index'] = $index;
        }

        // Go up for X lines
        if ($upSize > 0) {
            fwrite(STDOUT, sprintf("\e[%sA", $upSize));
        }
        // Draw the message
        fwrite(STDOUT, "\r\e[K" . $message . "\n");

        foreach ($testOutput->getDebugOutput() as $debugOutput) {
            fwrite(STDOUT, "\r\e[K" . $debugOutput . "\n");
        }

        // Set the size for future reference
        $testOutput->setLastOutputSize($size);

        // Redraw next test output if needed or set the cursor down
        foreach ($this->testOutputs as $testOutputIndex) {
            if ($testOutputIndex['index'] > $index) {
                $testOutput = $testOutputIndex['output'];

                $size = $testOutput->calculateHeightSize($this->columns);
                fwrite(STDOUT, "\r\e[K" . $testOutput->getMessage() . "\n");

                foreach ($testOutput->getDebugOutput() as $debugOutput) {
                    fwrite(STDOUT, "\r\e[K" . $debugOutput . "\n");
                }

                $testOutput->setLastOutputSize($size);
            }
        }
    }
}
