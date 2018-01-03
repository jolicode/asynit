<?php

declare(strict_types=1);

namespace Asynit\Extension\Chrome;

use function Amp\call;
use Amp\Deferred;
use Amp\Promise;

/**
 * Represent a chrome tab (page)
 */
class Tab
{
    private $target;

    private $networkManager;

    private $frameManager;

    private $loadFired;

    private $loadTime;

    public function __construct(Target $target, $frameTree)
    {
        $this->target = $target;

        $this->networkManager = new NetworkManager($target);
        $this->frameManager = new FrameManager($target, $frameTree, $this);
        $this->loadFired = new Deferred();

        $this->target->on('Runtime.consoleAPICalled', (new \ReflectionMethod($this, 'onConsoleAPI'))->getClosure($this));
        $this->target->on('Page.javascriptDialogOpening', (new \ReflectionMethod($this, 'onDialog'))->getClosure($this));
        $this->target->on('Runtime.exceptionThrown', (new \ReflectionMethod($this, 'onExceptionThrown'))->getClosure($this));
        $this->target->on('Security.certificateError', (new \ReflectionMethod($this, 'onCertificateError'))->getClosure($this));
        $this->target->on('Inspector.targetCrashed', (new \ReflectionMethod($this, 'onTargetCrashed'))->getClosure($this));
        $this->target->on('Performance.metrics', (new \ReflectionMethod($this, 'onMetrics'))->getClosure($this));
        $this->target->on('Security.certificateError', function ($event) {
            yield $this->target->send('Security.handleCertificateError', ['eventId' => $event['eventId'], 'action' => 'continue']);
        });

        $this->target->onOneTime('Page.loadEventFired', function ($event) {
            $this->loadFired->resolve($event['timestamp']);
            $this->loadFired = null;

            return true;
        });
    }

    public function waitForLoadEvent(): Promise
    {
        return Promise\timeout(call(function () {
            if (null === $this->loadTime) {
                $this->loadTime = yield $this->loadFired->promise();
            }

            return $this->loadTime;
        }), 60000);
    }

    private function onConsoleAPI($event)
    {
    }

    private function onDialog($event)
    {
    }

    private function onExceptionThrown($event)
    {
    }

    private function onCertificateError($event)
    {
    }

    private function onTargetCrashed($event)
    {
    }

    private function onMetrics($event)
    {
    }

    public function navigate(string $url): Promise
    {
        $responses = [];

        $deferred = new Deferred();

        // First we receive response from the network (we can have redirection etc ...) each response is attached to a frame
        $responseListener = $this->target->on('Network.responseReceived', function ($event) use (&$responses) {
            $responses[$event['frameId']] = $event['response'];
        });

        // Once the good response has been receive the frame will be navigated to it, so here we add an other listener, as we want the frame to be ready
        // Need more counter measure if we navigate too fast may be bloated here
        // Pupetter does a check on the url, but not on the passed one, more on the one attached to the frame (we use frameId, may be a bad idea)
        $this->target->onOneTime('Page.frameNavigated', function ($event) use ($deferred, &$responses, &$responseListener, $url) {
            $frame = $event['frame'];

            if (array_key_exists($frame['id'], $responses)) {
                $this->target->remove($responseListener);
                $deferred->resolve($responses[$frame['id']]);

                return true;
            }

            return false;
        });

        $this->target->send('Page.navigate', [
            'url' => $url
        ]);

        return $deferred->promise();
    }

    public function evaluate($expression): Promise
    {
        return \Amp\call(function () use ($expression) {
            $executionContext = yield $this->frameManager->getMainFrame()->getExecutionContext();

            return yield $executionContext->evaluate($expression);
        });
    }

    public function screenshot($fullPage = true, $width = 1920, $height = 1080): Promise
    {
        return call(function () use ($fullPage, $width, $height) {
            yield $this->waitForLoadEvent();

            $clip = [
                'width' => $width,
                'height' => $height,
                'x' => 0,
                'y' => 0,
                'scale' => 1,
            ];

            if ($fullPage) {
                $metrics = yield $this->target->send('Page.getLayoutMetrics');
                $clip = [
                    'width' => ceil($metrics['contentSize']['width']),
                    'height' => ceil($metrics['contentSize']['height']),
                    'x' => 0,
                    'y' => 0,
                    'scale' => 1,
                ];
            }

            // @TODO Allow other options - We should have a view port object
            yield $this->target->send('Emulation.setDeviceMetricsOverride', [
                'mobile' => false,
                'width' => $clip['width'],
                'height' => $clip['height'],
                'deviceScaleFactor' => 1,
                'screenOrientation' => [
                    'angle' => 0,
                    'type' => 'portraitPrimary'
                ],
            ]);

            $screenData = yield $this->target->send('Page.captureScreenshot', [
                'format' => 'png',
                'clip' => $clip,
            ]);

            return base64_decode($screenData['data']);
        });
    }

    public function getDom(): Promise
    {
        return $this->target->send('DOM.getDocument');
    }

    public function setViewport(int $width, int $height, bool $isMobile = false, int $deviceScaleFactor = 1, bool $isLandscape = false, bool $enableTouch = false): Promise
    {
        return call(function () use ($width, $height, $isMobile, $deviceScaleFactor, $isLandscape, $enableTouch) {
            $screenOrientation = $isLandscape ? [
                'angle' => 90,
                'type' => 'landscapePrimary'
            ] : [
                'angle' => 0,
                'type' => 'portraitPrimary'
            ];

            yield [
                $this->target->send('Emulation.setDeviceMetricsOverride', [
                    'mobile' => $isMobile,
                    'width' => $width,
                    'height' => $height,
                    'deviceScaleFactor' => $deviceScaleFactor,
                    'screenOrientation' => $screenOrientation
                ]),
                $this->target->send('Emulation.setTouchEmulationEnabled', [
                    'enabled' => $enableTouch,
                    'configuration' => $isMobile ? 'mobile' : 'desktop',
                ]),
            ];

            // @TODO create touch screen
            // @TODO Reload if we remove/set the touch screen
            // @TODO Reload if we add / remove mobile emulation
            // see https://github.com/GoogleChrome/puppeteer/blob/master/lib/EmulationManager.js
        });
    }
}
