<?php

namespace Asynit\Output;

class TestOutput
{
    private $debugOutput;

    private $message = '';

    private $lastOutputSize = 0;

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
        if (empty($debugOutput)) {
            return;
        }

        if (null === $this->debugOutput) {
            $this->debugOutput = "";
        }

        $this->debugOutput .= $debugOutput;
    }

    /**
     * Get the size of the output given a terminal size
     *
     * @param $columns
     *
     * @return int
     */
    public function calculateHeightSize($columns)
    {
        $linesArray = null === $this->debugOutput ? [] : preg_split('/\n|\r/', $this->debugOutput);
        $linesCount = ceil(strlen($this->message) / $columns);

        foreach ($linesArray as $line) {
            $linesCount += ceil(strlen($line) / $columns);
        }

        return (int) $linesCount;
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
    public function getDebugOutput()
    {
        $debugOutput = $this->debugOutput;

        if (null === $debugOutput) {
            return [];
        }

        $debugOutput = rtrim($debugOutput);

        return preg_split('/\n|\r/', $debugOutput);
    }
}
