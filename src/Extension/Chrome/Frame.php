<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use function Amp\call;
use Amp\Promise;
use Amp\Deferred;

class Frame
{
    private $target;

    private $tab;

    private $parentFrame;

    private $id;

    private $executionContext;

    private $executionContextDeferred;

    public function __construct(Target $target, Tab $tab, $id, Frame $parentFrame = null)
    {
        $this->target = $target;
        $this->tab = $tab;
        $this->id = $id;
        $this->parentFrame = $parentFrame;
        $this->executionContextDeferred = new Deferred();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function resolveContext(ExecutionContext $executionContext)
    {
        $this->executionContext = $executionContext;

        if ($this->executionContextDeferred !== null) {
            $this->executionContextDeferred->resolve($executionContext);
            $this->executionContextDeferred = null;
        }
    }

    public function getExecutionContext(): Promise
    {
        return call(function () {
            if ($this->executionContextDeferred) {
                $this->executionContext = yield $this->executionContextDeferred->promise();
                $this->executionContextDeferred = null;
            }

            return $this->executionContext;
        });
    }
}
