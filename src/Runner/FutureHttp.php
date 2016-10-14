<?php

namespace Asynit\Runner;

use Psr\Http\Message\RequestInterface;

class FutureHttp
{
    /** @var RequestInterface underlying request to execute */
    private $request;

    /** @var callable List of resolved callbacks */
    private $resolveCallback;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * When the result should resolve.
     *
     * @param callable $callback
     */
    public function shouldResolve(callable $callback)
    {
        $this->resolveCallback = $callback;
    }

    /**
     * @return callable
     */
    public function getResolveCallback()
    {
        return $this->resolveCallback;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }
}
