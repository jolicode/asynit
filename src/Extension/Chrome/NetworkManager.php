<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

class NetworkManager
{
    private $target;

    public function __construct(Target $target)
    {
        $this->target = $target;

        $this->target->on('Network.requestWillBeSent', (new \ReflectionMethod($this, 'onRequestWillBeSent'))->getClosure($this));
        $this->target->on('Network.requestIntercepted', (new \ReflectionMethod($this, 'onRequestIntercepted'))->getClosure($this));
        $this->target->on('Network.responseReceived', (new \ReflectionMethod($this, 'onResponseReceived'))->getClosure($this));
        $this->target->on('Network.loadingFinished', (new \ReflectionMethod($this, 'onLoadingFinished'))->getClosure($this));
        $this->target->on('Network.loadingFailed', (new \ReflectionMethod($this, 'onLoadingFailed'))->getClosure($this));
    }

    private function onRequestWillBeSent($event)
    {

    }

    private function onRequestIntercepted($event)
    {

    }

    private function onResponseReceived($event)
    {

    }

    private function onLoadingFinished($event)
    {

    }

    private function onLoadingFailed($event)
    {

    }
}
