<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use Amp\Promise;

class ExecutionContext
{
    private $target;

    private $id;

    private $isDefault;

    private $frameId;

    public function __construct(Target $target, $context)
    {
        $this->target = $target;
        $this->id = $context['id'];
        $this->isDefault = true;

        if (array_key_exists('auxData', $context)) {
            $this->frameId = $context['auxData']['frameId'];
            $this->isDefault = $context['auxData']['isDefault'];
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function getFrameId()
    {
        return $this->frameId;
    }

    public function evaluate($expression): Promise
    {
        return \Amp\call(function () use ($expression) {
            $evaluateResponse = yield $this->target->send('Runtime.evaluate', [
                'expression' => $expression,
                'contextId' => $this->id,
                'returnByValue' => false,
                'awaitPromise' => true,
            ]);

            // @TODO Parse response

            return $evaluateResponse;
        });
    }
}
