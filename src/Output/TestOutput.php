<?php

namespace Asynit\Output;

class TestOutput
{
    private $debugOutput = '';

    private $message = '';

    private $lastOutputSize = 0;

    private $index;

    public function __construct($index)
    {
        $this->index = $index;
    }

    /**
     * @return mixed
     */
    public function getIndex()
    {
        return $this->index;
    }

    public function decrementIndex()
    {
        --$this->index;
    }

    /**
     * @param mixed $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @param $debugOutput
     */
    public function addDebugOutput($debugOutput)
    {
        $this->debugOutput .= $debugOutput;
    }

    /**
     * Get the size of the output given a terminal size.
     *
     * @param $columns
     *
     * @return int
     */
    public function calculateHeightSize($columns)
    {
        $linesArray = $this->getOutput();
        $linesCount = 0;

        foreach ($linesArray as $line) {
            $linesCount += max(ceil($this->getDisplayLength($line) / $columns), 1);
        }

        return (int) $linesCount - 1;
    }

    /**
     * @return int
     */
    public function getLastOutputSize()
    {
        return $this->lastOutputSize;
    }

    /**
     * @param int $lastOutputSize
     */
    public function setLastOutputSize($lastOutputSize)
    {
        $this->lastOutputSize = $lastOutputSize;
    }

    /**
     * @return mixed
     */
    public function getOutput()
    {
        $message = $this->message.$this->debugOutput;

        return preg_split('/\n|\r/', $message);
    }

    protected function getDisplayLength(string $text)
    {
        $parsed = preg_replace('#\\x1b[[][^A-Za-z]*[A-Za-z]#', '', $text);
        $parsed = str_replace("\t", '        ', $parsed);

        return strlen($parsed);
    }
}
