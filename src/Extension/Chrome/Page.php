<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use Amp\Deferred;
use Amp\Promise;

class Page
{
    private $session;

    private $contextId;

    private $contextPromise;

    public function __construct(Session $session, $frameTree)
    {
        $deferredContextPromise = new Deferred();

        $this->session = $session;
        $this->session->on('Runtime.executionContextCreated', function ($event) use($deferredContextPromise) {
            $this->contextId = $event['context']['id'];
            $deferredContextPromise->resolve($event['context']['id']);
        });
        $this->contextPromise = $deferredContextPromise->promise();
    }

    public function navigate(string $uri)
    {
        $deferred = new Deferred();
        $listenerId = $this->session->on('Network.responseReceived', function ($event) use (&$listenerId, $deferred, $uri) {
            $response = $event['response'];

            // @TODO Use frame events to check on the correct url (main frame url should be used)
            if ($response['url'] === $uri) {
                $deferred->resolve($response);
                $this->session->remove($listenerId);
            }
        });

        $this->session->send('Page.navigate', [
            'url' => $uri
        ]);

        return $deferred->promise();
    }

    public function evaluate($expression): Promise
    {
        return \Amp\call(function () use ($expression) {
            yield $this->contextPromise;

            $evaluateResponse = yield $this->session->send('Runtime.evaluate', [
                'expression' => $expression,
                'contextId' => $this->contextId,
                'returnByValue' => false,
                'awaitPromise' => true,
            ]);

            return $evaluateResponse;
        });
    }

    public function screenshot(): Promise
    {
        return $this->session->send('Page.captureScreenshot', [
            'format' => 'png'
        ]);
    }

    public function getDom(): Promise
    {
        return $this->session->send('DOM.getDocument');
    }
}
