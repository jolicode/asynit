<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use function Amp\call;
use Amp\Promise;
use Amp\Deferred;

class Frame
{
    private $session;

    private $page;

    private $parentFrame;

    private $id;

    private $executionContext;

    private $executionContextDeferred;

    public function __construct(Session $session, Page $page, $id, Frame $parentFrame = null)
    {
        $this->session = $session;
        $this->page = $page;
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
