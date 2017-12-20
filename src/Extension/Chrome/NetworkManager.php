<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

class NetworkManager
{
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;

        $this->session->on('Network.requestWillBeSent', (new \ReflectionMethod($this, 'onRequestWillBeSent'))->getClosure($this));
        $this->session->on('Network.requestIntercepted', (new \ReflectionMethod($this, 'onRequestIntercepted'))->getClosure($this));
        $this->session->on('Network.responseReceived', (new \ReflectionMethod($this, 'onResponseReceived'))->getClosure($this));
        $this->session->on('Network.loadingFinished', (new \ReflectionMethod($this, 'onLoadingFinished'))->getClosure($this));
        $this->session->on('Network.loadingFailed', (new \ReflectionMethod($this, 'onLoadingFailed'))->getClosure($this));
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
