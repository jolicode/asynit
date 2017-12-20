<?php

namespace Asynit\HttpClient;

use Amp\Promise as AmpPromise;
use Http\Client\Exception;
use Http\Promise\Promise as HttpPromise;
use Psr\Http\Message\ResponseInterface;

class PromiseAdapter implements HttpPromise, AmpPromise
{
    private $state = HttpPromise::PENDING;

    private $response;

    private $exception;

    private $promise;

    /**
     * @var callable|null
     */
    private $onFulfilled;

    /**
     * @var callable|null
     */
    private $onRejected;

    public function __construct(AmpPromise $promise)
    {
        $this->promise = $promise;
        $this->promise->onResolve(function ($error, $result) {
            if (null !== $error) {
                if (!$error instanceof Exception) {
                    $error = new Exception\TransferException($error->getMessage(), $error->getCode(), $error);
                }

                $this->reject($error);
            } else {
                $this->resolve($result);
            }
        });
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null)
    {
        $deferred = new \Amp\Deferred();
        $newPromise = new self($deferred->promise());

        $onFulfilled = $onFulfilled ?? function (ResponseInterface $response) {
            return $response;
        };

        $onRejected = $onRejected ?? function (Exception $exception) {
            throw $exception;
        };

        $this->onFulfilled = function (ResponseInterface $response) use ($onFulfilled, $deferred) {
            try {
                $deferred->resolve($onFulfilled($response));
            } catch (Exception $exception) {
                $deferred->fail($exception);
            } catch (\Throwable $error) {
                $deferred->fail(new Exception\TransferException($error->getMessage(), $error->getCode(), $error));
            }
        };
        $this->onRejected = function (Exception $exception) use ($onRejected, $deferred) {
            try {
                $deferred->resolve($onRejected($exception));
            } catch (Exception $exception) {
                $deferred->fail($exception);
            } catch (\Throwable $error) {
                $deferred->fail(new Exception\TransferException($error->getMessage(), $error->getCode(), $error));
            }
        };

        if (HttpPromise::FULFILLED === $this->state) {
            $this->resolve($this->response);
        }

        if (HttpPromise::REJECTED === $this->state) {
            $this->reject($this->exception);
        }

        return $newPromise;
    }

    private function resolve(ResponseInterface $response)
    {
        $this->state = HttpPromise::FULFILLED;
        $this->response = $response;
        $onFulfilled = $this->onFulfilled;

        if (null !== $onFulfilled) {
            $onFulfilled($response);
        }
    }

    private function reject(Exception $exception)
    {
        $this->state = HttpPromise::REJECTED;
        $this->exception = $exception;
        $onRejected = $this->onRejected;

        if (null !== $onRejected) {
            $onRejected($exception);
        }
    }

    public function getState()
    {
        return $this->state;
    }

    public function wait($unwrap = true)
    {
        AmpPromise\wait($this->promise);

        if ($unwrap) {
            if (HttpPromise::REJECTED === $this->getState()) {
                throw $this->exception;
            }

            return $this->response;
        }
    }

    public function onResolve(callable $onResolved)
    {
        $this->promise->onResolve($onResolved);
    }
}
